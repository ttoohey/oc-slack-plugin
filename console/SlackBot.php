<?php

namespace Gency\Slack\Console;
declare(ticks = 1);

use Event;
use Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Clue\React\Block;
use Clue\React\Redis\Client as RedisClient;
use Clue\React\Redis\Factory as RedisFactory;
use React\EventLoop\Factory as EventLoopFactory;
use Slack\RealTimeClient;
use Gency\Slack\Models\Team;


class SlackBot extends Command
{
    protected $name = 'gency:slack';
    protected $description = 'A Slack client that listens for messages on subscribed channels';

    protected $connected;

    public function handle()
    {
        $this->connected = collect();
        $loop = EventLoopFactory::create();
        $this->subscribe($loop);
        foreach (Team::all() as $team) {
            $id = $team->getKey();
            $this->connected[$id] = $this->connectTeam($loop, $team);
        }
        $loop->run();
    }

    protected function subscribe($loop)
    {
        $factory = new RedisFactory($loop);
        $factory->createClient()->then(function (RedisClient $client) use ($loop) {
            $client->subscribe('gency.slack');
            $client->on('message', function ($channel, $message) use ($loop) {
                $action = json_decode($message, true);
                if ($action['type'] === 'addTeam') {
                    $team = Team::find($action['payload']['team']);
                    $this->connectTeam($loop, $team);
                }
                if ($action['type'] === 'removeTeam') {
                    $id = $action['payload']['team'];
                    if ($this->connected->keys()->contains($id)) {
                        $this->disconnectTeam($loop, $this->connected[$id]);
                        $this->connected = $this->connected->filter(function ($item) use ($id) { return $item !== $id; });
                    }
                }
            });
        })->otherwise(function ($e) {
            Log::error($e->getMessage());
        });
    }

    protected function connectTeam($loop, $team)
    {
        $client = new RealTimeClient($loop);
        $client->setToken($team->bot['bot_access_token']);
        $client->on('message', function ($data) use ($client, $team) {
            $loop = EventLoopFactory::create();
            if ($data['channel'][0] === 'D') {
                $channel = Block\await($client->getDMById($data['channel']), $loop);
            } else {
                $channel = Block\await($client->getChannelById($data['channel']), $loop);
            }
            $user = null;
            if (!empty($data['user'])) {
                $user = Block\await($client->getUserById($data['user']), $loop);
            }
            Event::fire('gency.slack.message', (object) [
                'team' => $team,
                'data' => $data,
                'client' => $client,
                'user' => $user,
                'channel' => $channel
            ]);
            $loop->run();
        });
        $client->on('goodbye', function () use ($client, $team) {
            $client->disconnect();
            sleep(1);
            $client->connect()->then(function () use ($client, $team) {
                Event::fire('gency.slack.connect', (object) [
                    'team' => $team,
                    'reconnect' => true
                ]);
            });
        });
        $client->connect()->then(function () use ($client, $team) {
            Event::fire('gency.slack.connect', (object) [
                'team' => $team,
                'reconnect' => false
            ]);
        });
        return $client;
    }

    protected function disconnectTeam($loop, $client)
    {
        $client->disconnect();
    }

    protected function getArguments()
    {
        return [];
    }

    protected function getOptions()
    {
        return [];
    }

}
