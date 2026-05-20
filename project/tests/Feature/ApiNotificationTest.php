<?php

use App\Exceptions\MessagingError;
use App\Exceptions\NotifyJobError;
use App\Jobs\NotifyJob;
use App\Models\Subscriber;
use App\Models\Notification;
use App\Services\Messenger\Email;
use App\Services\Messenger\MessengerResolver;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

dataset('test_data', [
    [
        'requestKey' => 'test_key_1',
        'subscribers' => [
            [
                'email' => 'pest_test_1@fakemail.ru',
                'phone' => '+7 111 000 00 01',
            ],
            [
                'email' => 'pest_test_2@fakemail.ru',
                'phone' => '+7 111 000 00 02',
            ],
            [
                'email' => 'pest_test_3@fakemail.ru',
                'phone' => '+7 111 000 00 03',
            ],
        ],
    ],
]);

function emails(array $subscibers): array
{
    return array_map(fn ($item) => $item['email'], $subscibers);
}

function clean(string $requestKey, array $subscribers)
{
    Subscriber::whereIn('email', emails($subscribers))->delete();
    Redis::del($requestKey);
}

test('Подгтовка', function (string $requestKey, array $subscribers) {
    clean($requestKey, $subscribers);

    expect(Subscriber::whereIn('email', emails($subscribers))->count())->toBe(0);
    expect(Redis::get($requestKey))->toBeNull();
})->with('test_data');

test('Зарегистрированы подписчики рассылки', function (string $requestKey, array $subscribers) {
    $emails = emails($subscribers);

    $response = $this->postJson('/api/subscribers', $subscribers[0]);
    $response->assertStatus(201);
    expect($response['data']['id'])->toBeGreaterThan(0);
    expect(Subscriber::whereIn('email', $emails)->count())->toBe(1);

    $response = $this->postJson('/api/subscribers', $subscribers[1]);
    expect(Subscriber::whereIn('email', $emails)->count())->toBe(2);

    $response = $this->postJson('/api/subscribers', $subscribers[2]);
    expect(Subscriber::whereIn('email', $emails)->count())->toBe(3);
})->with('test_data');

test('Сервис рассылки принял сообщение в работу', function (
    string $requestKey,
    array $subscribers,
) {
    Queue::fake();
    $emails = emails($subscribers);

    $subscribersIds = Subscriber::whereIn('email', $emails)->pluck('id');
    expect(count($subscribersIds))->toBe(3);

    $response = $this->postJson('/api/notifications', [
        'request_key' => $requestKey,
        'message' => 'pest testing message',
        'channel' => 'email',
        'priority' => 'low',
        'subscriber_ids' => $subscribersIds,
    ]);

    $response->assertStatus(202);
    $response->assertJson(['status' => 'Accepted']);
    expect(count($response['results']))->toBe(3);
    Queue::assertPushedTimes(NotifyJob::class, 3);
})->with('test_data');

test('Сервис рассылки корректно среагировал на сообщение с повторяющимся ключём', function (
    string $requestKey,
    array $subscribers,
) {
    Queue::fake();
    $emails = emails($subscribers);

    $subscribersIds = Subscriber::whereIn('email', $emails)->pluck('id');
    expect(count($subscribersIds))->toBe(3);

    $response = $this->postJson('/api/notifications', [
        'request_key' => $requestKey,
        'message' => 'pest testing message',
        'channel' => 'email',
        'priority' => 'low',
        'subscriber_ids' => $subscribersIds,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'Already processing']);
    Queue::assertNotPushed(NotifyJob::class);
})->with('test_data');

test('Сообщения были добавлены в БД', function (
    string $requestKey,
    array $subscribers,
) {
    $notifications = Notification::where('request_key', $requestKey)->get();
    expect(count($notifications))->toBe(3);

    $subsciber = Subscriber::where('email', $subscribers[0]['email'])->first();
    $notifications = Notification::where('subscriber_id', $subsciber->id)->get();
    expect(count($notifications))->toBe(1);
    expect($notifications[0]->status)->toBe('queued');
})->with('test_data');

test('Задание рассылки отправило сообщение', function (
    string $requestKey,
    array $subscribers,
) {
    $subsciber = Subscriber::where('email', $subscribers[0]['email'])->first();
    $notification = $subsciber->notifications()->where('request_key', $requestKey)->first();

    expect($notification->status)->toBe('queued');

    $gateway = new Email\MockGateway();
    $gateway->allowDelivery = false;
    $gateway->allowDelays = false;
    $gateway->allowFail = false;

    $job = new NotifyJob($notification->id);
    $job->handle(new MessengerResolver(['email' => $gateway]));

    $notification->refresh();
    expect($notification->status)->toBe('sent');
})->with('test_data');

test('Задание рассылки отказалось обрабатывать уже отправленное сообщение', function (
    string $requestKey,
    array $subscribers,
) {
    $subsciber = Subscriber::where('email', $subscribers[0]['email'])->first();
    $notification = $subsciber->notifications()->where('request_key', $requestKey)->first();

    expect($notification->status)->toBe('sent');

    $job = new NotifyJob($notification->id);
    $job->handle(new MessengerResolver([]));
})->with('test_data')->throws(NotifyJobError::class);

test('Задание рассылки доставило сообщение', function (
    string $requestKey,
    array $subscribers,
) {
    $subsciber = Subscriber::where('email', $subscribers[1]['email'])->first();
    $notification = $subsciber->notifications()->where('request_key', $requestKey)->first();

    expect($notification->status)->toBe('queued');

    $gateway = new Email\MockGateway();
    $gateway->allowDelays = false;
    $gateway->allowFail = false;

    $job = new NotifyJob($notification->id);
    $job->handle(new MessengerResolver(['email' => $gateway]));

    $notification->refresh();
    expect($notification->status)->toBe('delivered');
})->with('test_data');

test('Задание рассылки отбросило сообщение', function (
    string $requestKey,
    array $subscribers,
) {
    $subsciber = Subscriber::where('email', $subscribers[2]['email'])->first();
    $notification = $subsciber->notifications()->where('request_key', $requestKey)->first();

    expect($notification->status)->toBe('queued');

    $gateway = new Email\MockGateway();
    $gateway->allowDelays = false;
    $gateway->forceFail = true;

    $job = new NotifyJob($notification->id);

    try {
        $job->handle(new MessengerResolver(['email' => $gateway]));
    } catch (MessagingError $error) {
        $job->failed($error);
    }

    $notification->refresh();
    expect($notification->status)->toBe('rejected');
})->with('test_data');

test('Расчистка после теста', function (string $requestKey, array $subscribers) {
    clean($requestKey, $subscribers);

    expect(Subscriber::whereIn('email', emails($subscribers))->count())->toBe(0);
    expect(Redis::get($requestKey))->toBeNull();
})->with('test_data');
