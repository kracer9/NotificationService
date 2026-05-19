<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Notification;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Subscriber",
    title: "Subscriber",
    description: "Подписчик",
    required: ["id", "email", "phone"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "email", type: "string", format: "email", example: "example@example.com"),
        new OA\Property(property: "phone", type: "string", example: "+7 999 000 00 00"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2024-01-01T00:00:00Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2024-01-01T00:00:00Z")
    ]
)]
class Subscriber extends Model
{
    protected $fillable = [
        'phone', 'email',
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
