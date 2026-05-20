<?php

use App\Services\Messenger\MessengerResolver;
use App\Services\Messenger\Email;
use App\Services\Messenger\SMS;

test('Распознан канал для рассылки уведомлений', function () {
    $resolver = $this->app->make(MessengerResolver::class);

    expect($resolver->resolve('email'))->toBeInstanceOf(Email\GatewayInterface::class);
    expect($resolver->resolve('sms'))->toBeInstanceOf(SMS\GatewayInterface::class);
});
