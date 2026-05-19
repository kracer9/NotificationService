<?php

namespace App\Services\Notification;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

class ReaderService
{
    private int $pageSize = 100;

    public function list(int $page = 1): Collection
    {
        return Notification::
            skip($this->offset($page))
            ->take($this->pageSize)
            ->get();
    }

    public function listBySubscriber(int $subscriberId, int $page = 1): Collection
    {
        return Notification::
            where('subscriber_id', $subscriberId)
            ->skip($this->offset($page))
            ->take($this->pageSize)
            ->get();
    }

    private function offset(int $page): int
    {
        return $this->pageSize * ($page - 1);
    }
}
