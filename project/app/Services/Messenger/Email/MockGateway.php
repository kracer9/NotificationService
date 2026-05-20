<?php

namespace App\Services\Messenger\Email;

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
            throw new MessagingError('Email send error');
        }

        $subscriber = $notification->subscriber;
        $email = $subscriber->email;
        $message = $notification->message;

        Log::info("Email sent to: {$email}, message: {$message}");
        $onSent();

        if ($this->allowDelivery) {
            if ($this->allowDelays) {
                usleep(rand(500000, 1500000));
            }

            Log::info("Email deliveted to: {$email}, message: {$message}");
            $onDelivered();
        }
    }
}
