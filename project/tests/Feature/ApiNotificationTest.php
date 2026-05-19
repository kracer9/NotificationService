<?php

use App\Jobs\NotifyJob;
use App\Models\Subscriber;
use App\Models\Notification;
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

test('Подготовка', function (string $requestKey, array $subscribers) {
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
    $response->assertStatus(201);
    expect($response['data']['id'])->toBeGreaterThan(0);
    expect(Subscriber::whereIn('email', $emails)->count())->toBe(2);
})->with('test_data');

test('Сервис рассылки принял сообщение в работу', function (
    string $requestKey,
    array $subscribers,
) {
    Queue::fake();
    $emails = emails($subscribers);

    $subscribersIds = Subscriber::whereIn('email', $emails)->pluck('id');
    expect(count($subscribersIds))->toBe(count($subscribers));

    $response = $this->postJson('/api/notifications', [
        'request_key' => $requestKey,
        'message' => 'pest testing message',
        'channel' => 'email',
        'priority' => 'low',
        'subscribers' => $subscribersIds,
    ]);

    $response->assertStatus(202);
    $response->assertJson(['status' => 'Accepted']);
    Queue::shouldReceive(NotifyJob::class);
})->with('test_data');

test('Сервис рассылки корректно среагировал на сообщение с повторяющимся ключём', function (
    string $requestKey,
    array $subscribers,
) {
    Queue::fake();
    $emails = emails($subscribers);

    $subscribersIds = Subscriber::whereIn('email', $emails)->pluck('id');
    expect(count($subscribersIds))->toBe(count($subscribers));

    $response = $this->postJson('/api/notifications', [
        'request_key' => $requestKey,
        'message' => 'pest testing message',
        'channel' => 'email',
        'priority' => 'low',
        'subscribers' => $subscribersIds,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'Already processing']);
    Queue::shouldNotReceive(NotifyJob::class);
})->with('test_data');

test('Сообщения были добавлены в БД', function (
    string $requestKey,
    array $subscribers,
) {
    $notifications = Notification::where('request_key', $requestKey)->get();
    expect(count($notifications))->toBe(count($subscribers));

    $subsciber = Subscriber::where('email', $subscribers[0]['email'])->first();
    $notifications = Notification::where('subscriber_id', $subsciber->id)->get();
    expect(count($notifications))->toBe(1);
    expect($notifications[0]->status)->toBe('queued');
})->with('test_data');
