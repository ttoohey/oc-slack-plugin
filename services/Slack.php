<?php namespace Gency\Slack\Services;

use Illuminate\Http\Response;
use Clue\React\Block;
use React\EventLoop\Factory as EventLoopFactory;
use Slack\Message\Attachment;
use Slack\ApiClient;
use Gency\Slack\Models\Settings;
use Gency\Slack\Models\Verification;

class Slack
{
    /**
     * Begin OAuth grant flow authorize the app to a team
     *
     * https://api.slack.com/docs/oauth
     *
     * @param  array    $data   Application data returned via 'authorized' event (optional)
     * @param  string   $teamId Slack team ID of a workspace to attempt to restrict to (optional)
     * @return Response         Response object containing a redirect to the Slack authorization endpoint
     */
    static function authorize($data = null, $teamId = null)
    {
        $verification = Verification::create([
            'data' => $data
        ]);
        $query = http_build_query([
            'client_id' => Settings::get('client_id'),
            'scope' => 'commands,bot',
            'redirect_url' => url('slack/oauth/verify'),
            'state' => $verification->id,
            'team' => $teamId
        ]);
        return redirect('https://slack.com/oauth/authorize?' . $query);
    }

    /**
     * Helper function to simplify sending a reply to a channel that a message
     * has been received on.
     *
     * @param  object $event  gency.slack.message event object
     * @param  array $data    message data to send
     * @return Promise        Promise object that resolves with the API response payload
     */
    static function replyToMessage($event, $data)
    {
        $message = $event->client->getMessageBuilder()
            ->setText($data['text'])
            ->setChannel($event->channel)
            ->setUser($event->user);
        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $attachmentData) {
                $attachmentData += [
                    'fallback' => 'not supported',
                    'callback_id' => 'not specified',
                    'attachment_type' => 'default'
                ];
                $message->addAttachment(Attachment::fromData($attachmentData));
            }
        }
        return $event->client->postMessage($message->create());
    }
}
