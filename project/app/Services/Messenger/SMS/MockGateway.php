<?php

namespace App\Services\Messenger\SMS;

use App\Models\Notification;
use App\Exceptions\MessagingError;
use Illuminate\Support\Facades\Log;

class MockGateway implements GatewayInterface
{
    public bool $allowDelivery = true;
    public bool $allowDelays = true;
    public bool $allowFail = true;
    public bool $forceFail = false;

    public function send(Notification $notification, callable $onSent, callable $onDelivered)
    {
        if ($this->allowDelays) {
            usleep(500000);
        }

        if ($this->forceFail || ($this->allowFail && rand(1, 10) === 1)) {
            throw new MessagingError('SMS send error');
        }

        $subscriber = $notification->subscriber;
        $phone = $subscriber->phone;
        $message = $notification->message;

        Log::info("SMS sent to: {$phone}, message: {$message}");
        $onSent();

        if ($this->allowDelivery) {
            if ($this->allowDelays) {
                usleep(rand(100000, 100000));
            }

            Log::info("SMS delivered to: {$phone}, message: {$message}");
            $onDelivered();
        }
    }
}
