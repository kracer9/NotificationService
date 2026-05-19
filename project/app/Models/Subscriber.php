<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Notification;

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
