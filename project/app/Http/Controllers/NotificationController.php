<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Notification;
use App\Exceptions\NotificationDuplicate;

class NotificationController extends Controller
{
    public function publish(Request $request): JsonResponse
    {
        $notificationData = $request->validate([
            'request_key' => ['required', 'string'],
            'message' => ['required', 'string'],
            'channel' => ['required', 'in:sms,email'],
            'priority' => ['required', 'in:high,low'],
            'subscribers' => ['required', 'array'],
            'subscribers.*' => ['int'],
        ]);

        try {
            $service = new Notification\PublisherService();
            $service->publish($notificationData);
        } catch (NotificationDuplicate $exception) {
            $status = $exception->getMessage();
            return response()->json(['status' => $status], 200);
        }

        return response()->json(['status' => 'Accepted'], 202);
    }

    public function list(int $page = 1): JsonResponse
    {
        $service = new Notification\ReaderService();
        $notifications = $service->list($page);
        return response()->json(['data' => $notifications]);
    }

    public function listBySubscriber(string $subscriberId, int $page = 1): JsonResponse
    {
        $service = new Notification\ReaderService();
        $notifications = $service->listBySubscriber($subscriberId, $page);
        return response()->json(['data' => $notifications]);
    }
}
