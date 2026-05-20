<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Redis;
use App\Exceptions\NotificationDuplicate;
use App\Models\Notification;
use App\Jobs\NotifyJob;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Collection;

class PublisherService
{
    public function publish(array $data)
    {
        $this->controlDuplication($data);
        $subscribers = $this->findSubscribers($data);

        foreach ($subscribers as $subscriber) {
            $notification = $subscriber->notifications()->create([
                'request_key' => $data['request_key'],
                'message' => $data['message'],
                'channel' => $data['channel'],
                'priority' => $data['priority'],
                'status' => 'queued',
            ]);

            $this->toQueue($notification);
        }
    }

    private function controlDuplication(array $data)
    {
        if ($this->isDuplicate($data['request_key'])) {
            throw new NotificationDuplicate('Already processing');
        }
        $this->caching($data);
    }

    private function isDuplicate(string $key): bool
    {
        return !empty(Redis::get($key));
    }

    private function caching(array $data)
    {
        Redis::set($data['request_key'], $data);
    }

    private function findSubscribers(array $data): Collection
    {
        return Subscriber::whereIn('id', $data['subscriber_ids'])->get();
    }

    private function toQueue(Notification $notification)
    {
        $queue = $this->defineQueue($notification);
        NotifyJob::dispatch($notification->id)->onQueue($queue);
    }

    private function defineQueue(Notification $notification): string
    {
        return $notification->priority . "_priority";
    }
}
