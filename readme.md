# OctoberCMS Slack Plugin

A plugin for [OctoberCMS](http://octobercms.com) that allows connecting your plugin to [Slack](https://slack.com) teams.

Using the Slack API client from  https://github.com/sagebind/slack-client

## Installation

Install to the `plugins/gency/slack` folder of your OctoberCMS website.

## Configuration

Copy your Slack App's basic information to Backend > Settings > Bots > Slack App.

This information is available from you Slack account (https://api.slack.com/apps)

Complete your app's configuration by:

* Enabling "Interactive Components"
* Add a "Redirect URL" in "OAuth & Permissions"
* Adding a "Bot User"

### Redirect URL

Add `https://my.app.com/slack/oauth/verify`

(*replace *my.app.com* with your website's domain name*)

### Interactive components

For "Request URL" use `https://my.app.com/slack/action`

For "Options Load URL" use `https://my.app.com/slack/options`


## Adding teams

### Slack::authorize()

The `Gency\Slack\Services\Slack` service is used to authorize your app with a Slack team. The first parameter is custom data that will be passed through to your plugin if the user authorizes the app. It should contain a 'plugin' field that identifies your plugin, but can also contain any additional data relevant to your plugin.

```php
// plugins/acme/myplugin/controllers/MyController.php

use Gency\Slack\Services\Slack;

class MyController
{
    public function onAddToSlack()
    {
        return Slack::authorize([
            'plugin' => 'Acme.MyPlugin'
        ]);
    }
}
```

The `Slack::authorize()` method returns a Response object that redirects to the Slack OAuth authorisation page. If the user authorises the app they will be redirected back and the `gency.slack.authorized` event will be fired. Your plugin should listen for this event and redirect to an appropriate place.

```php
// plugins/acme/myplugin/init.php

Event::listen('gency.slack.authorized', function ($event) {
    if ($event->data['plugin'] === 'Acme.MyPlugin') {
        return redirect('/backend/acme/myplugin/mycontroller');
    }
});

```

### SlackButton widget

The `Gency\Slack\Widgets\SlackButton` widget can be used to put a button on one of your plugin's controller pages.

Bind the widget to your controller in your controller's constructor

```php
// plugins/acme/myplugin/controllers/MyController.php

class MyController extends Component
{
    public function __construct(Request $request)
    {
        parent::__construct();

        $slackButton = new SlackButton($this);
        $slackButton->bindToController();
        $slackButton->setData(['plugin' => 'Acme.MyPlugin']);
    }
}
```

Now the widget can be placed on a page.

```
// plugins/acme/myplugin/controllers/mycontroller/_list_toolbar.htm

<div data-control="toolbar">
    ...
    <?= $this->widget->slackButton->render() ?>
</div>

```

The button opens a confirmation modal. The modal needs to be rendered too.

```
// plugins/acme/myplugin/controllers/mycontroller/index.htm

<?= $this->listRender() ?>
<?= $this->widget->slackButton->renderModal() ?>
```

## Start the bot user

The `gency:slack` console command starts a client that connects as the bot user to all teams that have been authorised.

```sh
php artisan gency:slack
```

When a new team is authorised it will the client will connect to that team too. This requires a Redis server to be configured.

## Events

Your plugin should listen to events to provide the desired behaviours.

Event | Arguments | Description
--- | --- | ---
gency.slack.action  | { payload } | Fired when a message action has been received.
gency.slack.authorized | { team, data } | Fired when a team has been authorised. The `team` field contains a `Gency\Slack\Models\Team` instance. The `data` field contains the plugin-speific data that was provided via `Slack::authorize()`
gency.slack.authorization-cancelled  | { data }  | The user cancelled an authorization request via the Slack website. The `data` field contains the plugin-specific data that was provided via `Slack::authorize()`
gency.slack.connect  | { team, reconnect }  | The bot user has connected to a team. The `team` field contains the `Gency\Slack\Models\Team` instance for the team that has connected. The `reconnect` field is true if the connection occurred due to reconnect after the Slack service initiated a disconnect.
gency.slack.message  | { client, team, data, user, channel }  | The bot user has received a message from a channel it is a member of. `team` is the `Gency\Slack\Models\Team` instance. `data` is the message data. `user` is a instance of [User](http://sagebind.github.io/slack-client/api/class-Slack.User.html). `channel` is an instance of [Channel](http://sagebind.github.io/slack-client/api/class-Slack.Channel.html)
gency.slack.options | { payload } | Fired when a message options has been received.

## Useful helpers

The `Gency\Slack\Models\Team` model has some methods to help using the Slack API.

Method | Arguments | Description
--- | --- | ---
getChannelsAttribute  | () | Returns a collection containing the team's Slack channels. Also available as the `channels` property.
send  | ($text, $channelId)  | Sends a simple text message to the specified Slack channel.
postMessage | ($message, $channelId)  |  Sends a structured message to the specific Slack channel.

The `Gency\Slack\Services\Slack` service has the following static methods

Method | Arguments | Description
--- | --- | ---
Authorize | ($data\[, $teamId\]) | Request user to authorise the app to their team
replyToMessage  | ($event, $data)   |  Send a structured message in reply to a message
