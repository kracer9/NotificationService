<?php

namespace App\Services\Messenger\SMS;

use App\Models\Notification;
use App\Exceptions\MessagingError;
use Illuminate\Support\Facades\Log;

class MockGateway implements GatewayInterface
{
    public function send(Notification $notification)
    {
        usleep(500000);

        if (rand(1, 10) === 1) {
            throw new MessagingError('SMS send error');
        }

        $subscriber = $notification->subscriber;
        $phone = $subscriber->phone;
        $message = $notification->message;

        Log::info("SMS sent to: {$phone}, message: {$message}");
        $onSent();

        usleep(rand(100000, 100000));
        Log::info("SMS delivered to: {$phone}, message: {$message}");
        $onDelivered();
    }
}
