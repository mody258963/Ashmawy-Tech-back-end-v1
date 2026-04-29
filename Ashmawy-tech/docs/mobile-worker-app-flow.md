# Worker Mobile App Flow (Owner + Technician + Collector)

This document is the implementation contract for the mobile app developer.

Base URL example:

- `https://your-domain.com/api/v1`

Auth:

- All endpoints require `Authorization: Bearer <passport_token>`
- All endpoints below are behind `auth:api`

Login endpoints:

- `POST /auth/login` (public)
- `POST /auth/logout` (requires bearer token)

Login request example:

```json
{
  "login": "01000000000",
  "password": "secret123",
  "device_name": "android-worker-phone"
}
```

Login success response example:

```json
{
  "token_type": "Bearer",
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
  "user": {
    "id": 5,
    "name": "Collector User",
    "email": "collector@example.com",
    "phone": "01000000000",
    "role": "collector",
    "branch_id": 1
  }
}
```

---

## 1) Roles and responsibilities

- **Owner**
  - can log in to mobile app
  - can view all worker flows and admin-capable endpoints exposed by API
  - gets owner route hint from `/me` (`app_home = owner.dashboard`)

- **Technician**
  - sees assigned orders
  - updates work status
  - marks order as finished fixing (`ready`)
- **Collector**
  - picks up device from customer when order is `pending_pickup`
  - hands over device to technician
  - sees assigned orders pending delivery (orders in `ready`)
  - marks order as delivered (`delivered`)

---

## 2) Status lifecycle used by worker app

Main status values:

- `pending_pickup`
- `received`
- `diagnosing`
- `waiting_approval`
- `repairing`
- `ready` (finished fixing, waiting collector delivery)
- `delivered`
- `cancelled`

Worker app meaning:

- `ready` => **Pending Delivery** in collector UI

Service mode:

- `workshop` => normal collector + technician flow
- `home_service` => technician/owner home-visit flow (collector is skipped)

Required business sequence:

1. `pending_pickup`
2. collector picks up from customer -> `received`
3. technician fixes
4. technician marks finished -> `ready`
5. collector delivers to customer -> `delivered`

Home-service sequence:

1. order `service_mode = home_service`
2. `home_service_stage = home_visit_scheduled`
3. technician starts trip -> `on_the_way`
4. technician starts service -> `home_service_in_progress`
5. technician marks done -> `home_service_done` and order status `delivered`

---

## 3) Shared core endpoints already available

### Get current user

- `GET /me`

Use this first after login to detect role and branch.

Response includes route hints:

- `app_home`: app landing route key
- `allowed_flows`: operations allowed for this role

### List orders

- `GET /orders?status=<optional>&per_page=<optional>`

Behavior:

- Technician/collector only see orders assigned to them
- Branch filter is enforced server-side

### Get order details

- `GET /orders/{order}`

Includes:

- customer
- device
- branch
- technician, collector
- payments
- spare parts
- notes
- status history

### Generic status update (already exists)

- `PATCH /orders/{order}/status`
- body:

```json
{
  "status": "repairing"
}
```

Note:

- Collector and technician are blocked from this generic endpoint.
- Worker app must use role-specific endpoints in section 4.

---

## 4) New role-specific flow endpoints

### A) Collector picks up from customer

- `PATCH /collector/orders/{order}/pickup-from-customer`

Rules:

- requester must be role `collector`
- order must be assigned to this collector
- branch must match

Effect:

- status changes from `pending_pickup` to `received`
- `received_at` auto-set when empty
- status history row added

Success response (example):

```json
{
  "id": 55,
  "order_number": "AW-ABC123...",
  "status": "received",
  "received_at": "2026-04-22T10:00:00.000000Z"
}
```

### B) Technician marks finished fixing

- `PATCH /technician/orders/{order}/finish-fixing`

Rules:

- requester must be role `technician`
- order must be assigned to that technician
- branch must match

Effect:

- status changes to `ready`
- status history row added

Success response (example):

```json
{
  "id": 55,
  "order_number": "AW-ABC123...",
  "status": "ready",
  "delivered_at": null
}
```

### C) Collector list: pending delivery

- `GET /collector/orders/pending-delivery`

Rules:

- requester must be role `collector`
- only assigned collector orders
- only status `ready`
- branch match enforced

Success response shape:

```json
{
  "data": [
    {
      "id": 55,
      "order_number": "AW-ABC123...",
      "status": "ready",
      "workflow_status": "pending_delivery",
      "customer": {
        "id": 10,
        "name": "Customer Name",
        "phone": "010..."
      },
      "device": {
        "id": 20,
        "type": "Laptop",
        "brand": "HP",
        "model": "EliteBook"
      },
      "branch": {
        "id": 1,
        "name": "Main Branch"
      },
      "final_cost": "500.00",
      "received_at": "2026-04-20T10:00:00.000000Z",
      "delivered_at": null
    }
  ],
  "count": 1
}
```

### D) Collector marks delivered

- `PATCH /collector/orders/{order}/mark-delivered`

Rules:

- requester must be role `collector`
- order must be assigned to this collector
- branch match enforced

Effect:

- status changes to `delivered`
- `delivered_at` auto-set when empty
- status history row added

Success response (example):

```json
{
  "id": 55,
  "order_number": "AW-ABC123...",
  "status": "delivered",
  "delivered_at": "2026-04-22T14:12:05.000000Z"
}
```

### E) Home service: start trip (technician/owner)

- `PATCH /technician/orders/{order}/home-service/start-trip`

Rules:

- requester role must be `technician` or `owner`
- order must be assigned to technician (owner bypass allowed)
- order `service_mode` must be `home_service`
- `home_service_stage` must be `home_visit_scheduled`

Optional trip expense payload:

```json
{
  "trip_expense_amount": 120,
  "trip_expense_title": "Trip spare parts",
  "trip_expense_description": "Bought adapter before home visit"
}
```

Effect:

- home stage -> `on_the_way`
- order status -> `diagnosing`
- if expense amount sent: expense is created and linked to order (`order_id`)

### F) Home service: start work (technician/owner)

- `PATCH /technician/orders/{order}/home-service/start-service`

Rules:

- requester role `technician` or `owner`
- home-service order only
- stage must be `on_the_way` or `home_visit_scheduled`

Effect:

- home stage -> `home_service_in_progress`
- order status -> `repairing`

### G) Home service: mark done (technician/owner)

- `PATCH /technician/orders/{order}/home-service/mark-done`

Rules:

- requester role `technician` or `owner`
- home-service order only
- stage must be `home_service_in_progress` or `on_the_way`

Effect:

- home stage -> `home_service_done`
- order status -> `delivered`
- `delivered_at` is auto-set if empty

---

## 5) App screens contract

## 5.1 Technician Screens

### Screen T1: My Orders

Purpose:

- show technician assigned orders

API:

- `GET /orders?per_page=20`

UI groups (recommended):

- Active work: `received`, `diagnosing`, `waiting_approval`, `repairing`
- Finished fixing: `ready`

Card fields:

- order number
- customer name + phone
- device type/brand/model
- current status
- final/estimated cost

Actions:

- Open details (T2)

### Screen T2: Order Details

API:

- `GET /orders/{order}`

Sections:

- customer info
- device info
- timeline/status history
- spare parts used
- payments summary (read-only for technician)

Actions:

- update status via `PATCH /orders/{order}/status` (optional intermediate statuses)
- **Finish Fixing** button => `PATCH /technician/orders/{order}/finish-fixing`

Button visibility:

- show `Finish Fixing` only when status is one of:
  - `diagnosing`
  - `waiting_approval`
  - `repairing`
  - `received`

On success:

- local status becomes `ready`
- show toast: "Marked as finished fixing"
- optionally navigate back to list

## 5.2 Collector Screens

### Screen C0: Pickup Orders List

Purpose:

- collector sees orders to pick up from customer

API:

- `GET /orders?status=pending_pickup`

Display:

- order number
- customer name/phone
- customer address
- device summary
- branch

Actions:

- open details
- **Pickup from Customer** => `PATCH /collector/orders/{order}/pickup-from-customer`

On success:

- item disappears from pickup list
- order becomes visible in technician active list as `received`

### Screen C1: Pending Delivery List

Purpose:

- all collector orders waiting delivery

API:

- `GET /collector/orders/pending-delivery`

Display:

- order number
- customer name/phone
- address (if available from order/customer)
- device short info
- amount (`final_cost`)
- badge: `Pending Delivery`

Actions:

- open details (C2)
- quick mark delivered (optional swipe/button)

### Screen C2: Delivery Details

API:

- preferably from list item data + optional refresh `GET /orders/{order}`

Display:

- full customer + device info
- amount to collect (`final_cost`)
- payment history (optional)
- parts/services summary (optional)

Actions:

- **Mark Delivered** => `PATCH /collector/orders/{order}/mark-delivered`

On success:

- remove from pending list
- show toast: "Order delivered successfully"

---

## 6) Error handling contract

Common status codes:

- `401` unauthenticated (token invalid/expired)
- `403` forbidden (wrong role, wrong assignment, wrong branch)
- `404` order not found
- `422` validation/business error

Error body can be Laravel default. App should handle safely:

- show message from server when available
- fallback message:
  - `403`: "You are not allowed for this action."
  - `422`: "Invalid action or data."

---

## 7) Role-based app routing after login

After `GET /me`:

- if role = `owner` -> open Owner Home/Dashboard
- if role = `technician` -> open Technician Home (T1)
- if role = `collector` -> open Collector Home (C1)
- if role = `moderator|cashier` -> either block or route to separate app section (if implemented)

---

## 8) Recommended API call sequence

Technician path:

1. `GET /me`
2. `GET /orders`
3. `GET /orders/{id}`
4. `PATCH /technician/orders/{id}/finish-fixing`

Home-service technician path:

1. `GET /me`
2. `GET /orders?service_mode=home_service`
3. `PATCH /technician/orders/{id}/home-service/start-trip`
4. `PATCH /technician/orders/{id}/home-service/start-service`
5. `PATCH /technician/orders/{id}/home-service/mark-done`

Collector path:

1. `GET /me`
2. `GET /orders?status=pending_pickup`
3. `PATCH /collector/orders/{id}/pickup-from-customer`
4. technician works and marks ready
5. `GET /collector/orders/pending-delivery`
6. `PATCH /collector/orders/{id}/mark-delivered`
7. refresh pending delivery list

---

## 9) Notes for backend/frontend sync

- Collector "pending delivery" is not a separate DB status; it is `ready`.
- Do not hardcode English labels in app; map status keys to localized UI text.
- Status updates already write to `order_status_histories`, so timeline can be rendered from backend data.

---

## 10) Transition matrix

Allowed worker transitions:

- collector: `pending_pickup` -> `received` (`/collector/orders/{order}/pickup-from-customer`)
- technician: `received|diagnosing|waiting_approval|repairing` -> `ready` (`/technician/orders/{order}/finish-fixing`)
- collector: `ready` -> `delivered` (`/collector/orders/{order}/mark-delivered`)
- technician/owner (home service): `home_visit_scheduled` -> `on_the_way` (`/technician/orders/{order}/home-service/start-trip`)
- technician/owner (home service): `on_the_way|home_visit_scheduled` -> `home_service_in_progress` (`/technician/orders/{order}/home-service/start-service`)
- technician/owner (home service): `home_service_in_progress|on_the_way` -> `home_service_done` + status `delivered` (`/technician/orders/{order}/home-service/mark-done`)

Blocked transitions:

- any worker transition outside allowed sequence returns `422`
- role mismatch or order not assigned returns `403`
- collector/technician direct use of generic `PATCH /orders/{order}/status` returns `403`

---

## 11) Appointments module (for app developer)

Assignment rules:

- Appointment assignee (`technician_id`) can be either:
  - `technician`
  - `owner`
- Other roles cannot be set as appointment assignee.

Visibility in mobile app:

- The appointments app/module must show appointments **only for the assigned person**.
- If logged-in user is technician, return appointments where `technician_id == current_user.id`.
- If logged-in user is owner, return appointments where `technician_id == current_user.id` (owner assigned to himself).

API endpoints used:

- `GET /appointments` -> assigned appointments only
- `GET /appointments/{appointment}` -> only if this appointment is assigned to current user (or moderator)
- `PATCH /appointments/{appointment}` -> only assigned person (or moderator)
- `PATCH /appointments/{appointment}/status` -> only assigned person (or moderator)

Admin-side creation/update:

- Use admin panel to create appointment and choose assignee (owner/technician).
- Required fields: customer + scheduled date/time.
- Optional fields: address + address link + notes.

