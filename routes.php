<?php namespace Gency\Slack;

use Event;
use Log;
use Route;
use Illuminate\Http\Request;
use Clue\React\Block;
use React\EventLoop\Factory as EventLoopFactory;
use Slack\ApiClient;
use Gency\Slack\Models\Settings;
use Gency\Slack\Models\Team;
use Gency\Slack\Models\Verification;

Route::get('slack/oauth/verify', function (Request $request) {
    $code = $request->input('code');
    $state = $request->input('state');
    $error = $request->input('error');

    $verification = Verification::find($state);
    if (empty($verification)) {
        Log::error('Invalid authorization request. state=' . $state);
        return response('Invalid state', 403);
    }

    if (!empty($error)) {
        $event = (object) [
            'data' => $verification->data
        ];
        $verification->delete();
        $response = Event::fire('gency.slack.authorization-cancelled', $event, true);
        if (!$response) {
            Log::warn('Slack authorization was cancelled, but was not handled.');
            return response('Unhandled authorization', 404);
        }
        return $response;
    }

    $loop = EventLoopFactory::create();
    $client = new ApiClient($loop);
    $payload = Block\await($client->apiCall('oauth.access', [
        'client_id' => Settings::get('client_id'),
        'client_secret' => Settings::get('client_secret'),
        'code' => $code
    ]), $loop);
    $loop->run();

    $data = collect($payload->getData())->toArray();
    $team = Team::create($data);
    $event = (object) [
        'team' => $team,
        'data' => $verification->data
    ];
    $verification->delete();
    $response = Event::fire('gency.slack.authorized', $event, true);
    if (!$response) {
        Log::warn('Slack authorization was verified, but was not handled.');
        return response('Unhandled authorization', 404);
    }
    return $response;
});

Route::post('slack/action', function (Request $request) {
    $payload = json_decode($request->input('payload'), true);
    $token = $payload['token'];
    if ($token !== Settings::get('verification_token')) {
        Log::error("Slack action authorization invalid. Received '$token'");
        return response('Authorization invalid', 401);
    }
    $event = (object) [
        'payload' => $payload
    ];
    return Event::fire('gency.slack.action', $event, true);
});

Route::post('slack/options', function (Request $request) {
    $payload = json_decode($request->input('payload'), true);
    $token = $payload['token'];
    if ($token !== Settings::get('verification_token')) {
        return response('Authorization invalid', 401);
    }
    $event = (object) [
        'payload' => $payload
    ];
    return Event::fire('gency.slack.options', $event, true);
});
