<?php namespace Gency\Slack\Services;

use Illuminate\Support\Facades\Redis;

class Bot
{
    static function dispatch($type, $payload)
    {
        return Redis::publish('gency.slack', json_encode([
            'type' => $type,
            'payload' => $payload
        ]));
    }
}
