<?php

namespace App\Services\Messenger;

use App\Models\Notification;

interface MessengerGateway
{
    public function send(
        Notification $notification,
        callable $onSent,
        callable $onDelivered,
    );
}
