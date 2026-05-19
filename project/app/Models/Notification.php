<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Subscriber;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Notification",
    title: "Notification",
    description: "Уведомление подписчику",
    required: ["id", "request_key", "message", "channel", "priority", "status"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "request_key", type: "string", example: "key_example"),
        new OA\Property(property: "message", type: "string", example: "Example message"),
        new OA\Property(property: "channel", type: "string", enum: ["email", "sms"], example: "email"),
        new OA\Property(property: "priority", type: "string", enum: ["high", "low"], example: "low"),
        new OA\Property(
            property: "status",
            type: "string",
            enum: ["queued", "sent", "delivered", "rejected"],
            default: "queued",
            example: "queued"
        ),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2024-01-01T00:00:00Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2024-01-01T00:00:00Z")
    ]
)]
class Notification extends Model
{
    protected $fillable = [
        'request_key', 'message', 'channel', 'priority', 'status',
    ];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
