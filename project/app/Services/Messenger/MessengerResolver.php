<?php

namespace App\Services\Messenger;

use App\Exceptions\UnkownChannel;

class MessengerResolver
{
    /**
     * @var array<string, MessengerGateway>
     */
    private array $gateways;

    public function __construct(array $gateways)
    {
        $this->gateways = $gateways;
    }

    public function resolve(string $channel): MessengerGateway
    {
        if (isset($this->gateways[$channel])) {
            return $this->gateways[$channel];
        }
        throw new UnkownChannel("Unknown channel: {$channel}");
    }
}
