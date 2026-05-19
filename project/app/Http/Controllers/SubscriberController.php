<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Subscriber;
use OpenApi\Attributes as OA;

class SubscriberController extends Controller
{
    #[OA\Post(
        path: '/subscribers',
        tags: ['Subscribers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "phone"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "example@example.com"),
                    new OA\Property(property: "phone", type: "string", example: "+7 999 000 00 00"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Подписчик сохранён',
                content: new OA\JsonContent(
                    examples: [
                        new OA\Examples(
                            example: "id",
                            summary: "Object with subscriber id",
                            value: [
                                "id" => 1
                            ]
                        )
                    ]
                )
            )
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string'],
            'phone' => ['required', 'string'],
        ]);

        $subscriber = Subscriber::create($data);
        $result = ['id' => $subscriber->id];

        return response()->json(['data' => $result], 201);
    }

    #[OA\Get(
        path: '/subscribers',
        tags: ['Subscribers'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список подписчиков (первая страница)',
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Subscriber")
                )
            )
        ],
    )]
    #[OA\Get(
        path: '/subscribers/page/{page}',
        tags: ['Subscribers'],
        parameters: [
            new OA\Parameter(
                name: "page",
                description: "Номер страницы",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список подписчиков (постраничный)',
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Subscriber")
                )
            )
        ],
    )]
    public function list(int $page = 1): JsonResponse
    {
        $pageSize = 100;
        $offset = $pageSize * ($page - 1);
        $result = Subscriber::skip($offset)->take($pageSize)->get();
        return response()->json(['data' => $result]);
    }
}
