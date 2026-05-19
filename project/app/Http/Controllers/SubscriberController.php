<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Subscriber;

class SubscriberController extends Controller
{
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

    public function list(int $page = 1): JsonResponse
    {
        $pageSize = 100;
        $offset = $pageSize * ($page - 1);
        $result = Subscriber::skip($offset)->take($pageSize)->get();
        return response()->json(['data' => $result]);
    }
}
