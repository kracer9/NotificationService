<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Exceptions\NotifyJobError;
use App\Services\Messenger\MessengerGateway;
use App\Services\Messenger\MessengerResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyJob implements ShouldQueue
{
    use Queueable;

    private int $notificationId;
    private Notification $notification;
    private MessengerGateway $gateway;

    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
        $this->notification = Notification::findOrFail($notificationId);
    }

    public function tries(): int
    {
        $priority = $this->notification->priority;
        return config("notification.worker_by_priority.$priority.tries");
    }

    public function backoff(): int
    {
        $priority = $this->notification->priority;
        return config("notification.worker_by_priority.$priority.backoff");
    }

    public function handle(MessengerResolver $resolver): void
    {
        $this->checkNotification();
        $this->resolveGateway($resolver);
        $this->sendMessage();
    }

    public function failed(\Throwable $exception)
    {
        $this->changeStatus('rejected');
        Log::error("Notify Job failed.
            notificationId: {$this->notificationId},
            error: {$exception->getMessage()}.");
    }

    private function checkNotification()
    {
        if (empty($this->notification)) {
            throw new NotifyJobError("Not found notification: {$this->notificationId}");
        }
        if ($this->notification->status !== 'queued') {
            throw new NotifyJobError("Wrong notification status: {$this->notification->status}");
        }
    }

    private function resolveGateway(MessengerResolver $resolver)
    {
        $this->setGateway($resolver->resolve($this->notification->channel));
    }

    private function setGateway(MessengerGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    private function sendMessage()
    {
        $this->gateway->send(
            $this->notification,
            fn () => $this->onSent(),
            fn () => $this->onDelivered(),
        );
    }

    private function onSent()
    {
        $this->changeStatus('sent');
    }

    private function onDelivered()
    {
        // todo:
        // В идеале шлюз рассылки должен бы вызвать webhook
        // для полной ассинхронности
        $this->changeStatus('delivered');
    }

    private function changeStatus(string $status)
    {
        if ($this->notification) {
            $this->notification->update(['status' => $status]);
        }
    }
}
