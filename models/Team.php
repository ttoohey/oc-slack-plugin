<?php namespace Gency\Slack\Models;

use Log;
use Model;

use React\EventLoop\Factory;
use Slack\ApiClient;
use Gency\Slack\Services\Bot as SlackBot;

/**
 * Model
 */
class Team extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'gency_slack_teams';

    public $fillable = [
        'access_token',
        'scope',
        'team_name',
        'team_id',
        'incoming_webhook',
        'bot'
    ];

    public $casts = [
        'incoming_webhook' => 'json',
        'bot' => 'json'
    ];

    public function afterSave()
    {
        SlackBot::dispatch('addTeam', [ 'team' => $this->id ]);
    }

    public function beforeDelete()
    {
        SlackBot::dispatch('removeTeam', [ 'team' => $this->id ]);
    }

    public function send($text, $channelId)
    {
        if (empty($this->bot['bot_access_token']) || empty($channelId)) {
            return false;
        }
        $loop = Factory::create();
        $client = new ApiClient($loop);
        $client->setToken($this->bot['bot_access_token']);
        $result = false;
        $client->getChannelById($channelId)->then(function (\Slack\Channel $channel) use ($client, $text, &$result) {
            $client->send($text, $channel)->then(function ($payload) use (&$result) {
                $data = $payload->getData();
                $result = $data['ok'];
            })->otherwise(function (\Exception $e) use ($channel) {
                Log::error('Unable to post message to #' . $channel->getName() . ': ' . $e->getMessage());
            });
        });
        $loop->run();
        return $result;
    }

    public function postMessage($message, $channelId)
    {
        if (empty($this->bot['bot_access_token']) || empty($channelId)) {
            return false;
        }
        $loop = Factory::create();
        $client = new ApiClient($loop);
        $client->setToken($this->bot['bot_access_token']);
        $result = false;
        $client->getChannelById($channelId)->then(function (\Slack\Channel $channel) use ($client, $message, &$result) {
            $client->postMessage($message, $channel)->then(function ($payload) use (&$result) {
                $data = $payload->getData();
                $result = $data['ok'];
            })->otherwise(function (\Exception $e) {
                Log::error('Unable to post message: ' . $e->getMessage());
            });
        });
        $loop->run();
        return $result;
    }

    public function getChannelsAttribute()
    {
        $loop = Factory::create();
        $client = new ApiClient($loop);
        $client->setToken($this->bot['bot_access_token']);
        $channels = null;
        $client->getChannels()->then(function ($response) use ($loop, &$channels) {
            $channels = $response;
        });
        $loop->run();
        return collect($channels);
    }
}
