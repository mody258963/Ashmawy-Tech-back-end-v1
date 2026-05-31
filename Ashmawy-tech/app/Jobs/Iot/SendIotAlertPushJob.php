<?php

namespace App\Jobs\Iot;

use App\Models\Iot\IotPushToken;
use App\Services\Iot\FcmNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendIotAlertPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, string>  $data
     */
    public function __construct(
        public int $iotUserId,
        public int $iotDeviceId,
        public string $title,
        public string $body,
        public array $data = [],
    ) {
        $this->onQueue(config('iot.queue', 'iot'));
    }

    public function handle(FcmNotificationService $fcm): void
    {
        $tokens = IotPushToken::query()
            ->where('iot_user_id', $this->iotUserId)
            ->pluck('token')
            ->all();

        $fcm->sendToTokens($tokens, $this->title, $this->body, $this->data, highPriority: true);
    }
}
