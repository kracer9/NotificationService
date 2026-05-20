<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Notification;
use App\Exceptions\NotificationDuplicate;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    #[OA\Post(
        path: '/api/notifications',
        tags: ['Notifications'],
        summary: "Передать сервису уведомление для рассылки",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["request_key", "message", "channel", "priority", "subscribers"],
                properties: [
                    new OA\Property(property: "request_key", type: "string", example: "example_key"),
                    new OA\Property(property: "message", type: "string", example: "Example message"),
                    new OA\Property(property: "channel", type: "string", enum: ["email", "sms"], example: "email"),
                    new OA\Property(property: "priority", type: "string", enum: ["high", "low"], example: "low"),
                    new OA\Property(
                        property: "subscriber_ids",
                        type: "array",
                        items: new OA\Items(type: "integer"),
                        example: [1,2,3]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: 'Уведомление принято в работу',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "status",
                            type: "string",
                            example: "Accepted"
                        ),
                        new OA\Property(
                            property: "results",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(
                                        property: "subscriber_id",
                                        type: "integer",
                                        example: "1"
                                    ),
                                    new OA\Property(
                                        property: "notification_id",
                                        type: "integer",
                                        example: "1"
                                    ),
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 200,
                description: 'Уведомление уже в работе',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "status",
                            type: "string",
                            example: "Already processing"
                        ),
                    ]
                )
            )
        ],
    )]
    public function publish(Request $request): JsonResponse
    {
        $notificationData = $request->validate([
            'request_key' => ['required', 'string'],
            'message' => ['required', 'string'],
            'channel' => ['required', 'in:sms,email'],
            'priority' => ['required', 'in:high,low'],
            'subscriber_ids' => ['required', 'array'],
            'subscribers_ids.*' => ['int'],
        ]);

        try {
            $service = new Notification\PublisherService();
            $results = $service->publish($notificationData);
        } catch (NotificationDuplicate $exception) {
            $status = $exception->getMessage();
            return response()->json(['status' => $status], 200);
        }

        return response()->json([
            'status' => 'Accepted',
            'results' => $results,
        ], 202);
    }

    #[OA\Get(
        path: '/api/notifications',
        tags: ['Notifications'],
        summary: "Получить список уведомлений",
        parameters: [
            new OA\Parameter(
                name: "page",
                description: "Номер страницы",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список уведомлений',
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Notification")
                )
            )
        ],
    )]
    public function list(int $page = 1): JsonResponse
    {
        $service = new Notification\ReaderService();
        $notifications = $service->list($page);
        return response()->json(['data' => $notifications]);
    }

    #[OA\Get(
        path: '/api/notifications/by_subscriber/{subscriberId}',
        tags: ['Notifications'],
        summary: "Получить список уведомлений подписчика",
        parameters: [
            new OA\Parameter(
                name: "subscriberId",
                description: "Идентификатор подписчика",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "page",
                description: "Номер страницы",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список уведомлений',
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Notification")
                )
            )
        ],
    )]
    public function listBySubscriber(string $subscriberId, int $page = 1): JsonResponse
    {
        $service = new Notification\ReaderService();
        $notifications = $service->listBySubscriber($subscriberId, $page);
        return response()->json(['data' => $notifications]);
    }
}
