# دليل منزل ذكي — 10 مرحلات + 5 حساسات (ESP32 + Ashmawy IoT)

يهدف هذا الملف إلى **مطوري الأنظمة المدمجة** و**مشرفي الباك إند** لربط منزل كامل تقريباً: **10 مخرجات مرحل** عبر قنوات MQTT، و**5 حساسات** بنشر قيم **bool / int / float** على مواضيع الحساس.

- مثال البرمجة الثابتة: `docs/firmware/AshmawyEsp32HomeHubDemo/AshmawyEsp32HomeHubDemo.ino`  
- مرجع عام للمنصة: [iot-platform.md](./iot-platform.md)  
- مجموعة Postman: `postman/Ashmawy-Iot-Flutter-API.postman_collection.json`

---

## ١) مبدأ التصميم في الباك إند

| المفهوم | الشرح |
|---------|--------|
| **جهاز واحد (`iot_devices`)** | يمثل لوحة ESP32 واحدة في المنزل. |
| **مكوّن (`iot_components`)** | كل **مرحل** = مكوّن بنوع مثل `switch` أو `generic` ورقم **`channel` ثابت بين 1 و 255**. |
| **قناة MQTT** | الأوامر تذهب إلى `.../component/{channel}/set` حيث `{channel}` = **`iot_components.channel`**. |
| **الحساسات** | لا تحتاج صفاً في الجدول لكي تعمل MQTT؛ الجهاز ينشر إلى `.../sensor/{type}` حيث **`type`** سلسلة فريدة (مثل `temp_adc`). الخادم يخزن في Redis / اختياري DB حسب الإعدادات. |

لـ **10 مرحلات**: أنشئ **10 صفوف** `iot_components` لنفس `iot_device_id` بقنوات **1، 2، …، 10** (كل قناة فريدة لكل جهاز).

---

## ٢) خطوات الباك إند (ملخص)

1. **مستخدم IoT** + **جهاز** + **`mqtt_username`** + إصدار **JWT الجهاز** (`POST .../devices/{id}/jwt/regenerate`).  
2. أنشئ المكوّنات (من لوحة `/iot` أو API):

   `POST /api/v1/iot/devices/{id}/components`  

   جسم مثال لكل مرحل:

   ```json
   { "name": "Relay living", "type": "switch", "channel": 3, "metadata": null }
   ```

   كرر حتى **`channel` من 1 إلى 10** (أسماء مختلفة، قنوات مختلفة).  
3. شغّل **`php artisan queue:work redis --queue=iot`** و **`php artisan iot:mqtt-subscribe`**.  
4. من Postman: **تسجيل الدخول** ثم **`POST .../components/{id}/action`** مع:

   ```json
   { "action": "ON", "value": null }
   ```

   استخدم **`id`** الرقمي للمكوّن من قائمة المكوّنات (ليس `channel` في الرابط — راجع مسارات الـ API في `iot-platform.md`).

---

## ٣) حقل **`ack_outcome`** على سجل الإجراء (جدول `iot_device_actions`)

عند استدعاء **إجراء مكوّن** من المستخدم:

- يُنشأ سجل إجراء بحالة أولية **`pending`**.  
- بعد انتظار ACK من الجهاز (Redis):  
  - **`acknowledged`** — وصلت حالة `.../status` وتطابق `message_id` و **`command_ack` = true**.  
  - **`nack`** — الجهاز رد لكن **`command_ack` = false**.  
  - **`timeout`** — انتهى الوقت دون مطابقة.  
  - **`no_wait`** — الطلب أُرسل مع **`wait_ack_timeout_ms` = 0** (لا انتظار).

كما تم تعديل المنطق بحيث **`iot_components.last_state`** يُحدَّث من **رسالة الحالة `.../status` فقط** عندما يكون **`command_ack` = true** في الحمولة (أي بعد تأكيد الجهاز)، وليس عند مجرد استلام أمر `set` على الخادم من الوسيط.

استجابة الـ API للإجراء تتضمن الآن أيضاً **`ack_outcome`**.

**تطبيقات Flutter (حالة الزر):** عند **`wait_for_ack: true`** (الافتراضي)، الخادم يعيد **`200`** فقط إذا أكّد الـ ESP32 (`device_applied_command = true`). إذا انتهى الوقت دون رد يُعاد **`504`** مع `message`: **`device_ack_timeout`**، وإذا رفض الجهاز صراحةً يُعاد **`422`** مع **`device_rejected_command`**. حدّث واجهة الزر فقط عند **`response.statusCode == 200`** (أو تحقق صريح من **`device_applied_command`**). عند **`wait_for_ack: false`** يبقى **`200`** دائماً؛ لا تعتمد على تبديل الزر محلياً بل حدّث من **`GET .../status`** أو **`statuses`** عند وصول الحالة من الجهاز.

---

## ٤) إعداد الـ ESP32 (`AshmawyEsp32HomeHubDemo.ino`)

### ثوابت يجب تعديلها

| الثابت | المطلوب |
|--------|---------|
| **`IOT_USER_ID`** | نفس `iot_users.id` في الموضوع. |
| **`DEVICE_UUID`** | نفس `device_uuid` في قاعدة البيانات. |
| **`RELAY_PINS[10]`** | مصفوفة GPIO لكل قناة 1→10 (العنصر 0 = قناة 1، إلخ). |
| **`SENSOR_DEFS`** | خمسة صفوف: **`type`** (اسم الحساس في MQTT)، **`pin`**، **`kind`** (bool/int/float)، **`usePullup`**، **`intervalMs`**. |
| **Wi‑Fi / MQTT** | نفس وسيط EMQX؛ يُفضّل **JWT الجهاز** ككلمة مرور MQTT. |

### سلوك المرحلات

- الاشتراك: `iot/{user}/{uuid}/component/+/set` بجودة **QoS 1**.  
- عند **`ON` / `OFF` / `TOGGLE`**: يُحرَّك الدبوس ثم يُنشر **`.../component/{ch}/status`** مع **`message_id`** المأخوذ من أمر Laravel و **`command_ack`: true** حتى يعمل انتظار الـ API.

### سلوك الحساسات

- النشر إلى: `iot/{user}/uuid/sensor/{type}` بصيغة مثل `{"v":...}` (**QoS 0** في المثال).  
- اضبط **`intervalMs`** لتقليل الازدحام على الشبكة.

**تنبيه:** راجع **دatasheet لوحتك** (مثلاً ESP32-S3): بعض الدبابيس للإقلاع فقط أو ADC فقط؛ عدّل المصفوفات قبل التركيب الفعلي.

---

## ٥) اختبار سريع

1. **MQTTX**: اشترك في `iot/{user}/{uuid}/#` وراقب `set` و`status` والحساسات.  
2. **Postman**: `ON`/`OFF` لكل `component_id` يقابل قنوات 1–10.  
3. **Serial Monitor** على الـ ESP32 للتأكد من استلام المواضيع.

---

## ٦) توسيع المنزل لاحقاً

- أكثر من 10 مرحلات: زد المصفوفة في الكود أو استخدم عدة أجهزة في قاعدة البيانات.  
- حساسات إضافية: أضف عناصر في **`SENSOR_DEFS`** مع **`type`** فريد لكل حساس.  
- أتمتة: ربط `AutomationEngineStub` لاحقاً عند الحاجة.

بهذا يصبح المسار: **قاعدة بيانات (قنوات 1–10) ↔ Postman/Flutter ↔ MQTT ↔ ESP32 (دبابيس + نشر حساسات)**.
