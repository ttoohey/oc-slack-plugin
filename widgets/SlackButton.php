<?php namespace Gency\Slack\Widgets;

use Backend\Classes\WidgetBase;
use Gency\Slack\Services\Slack;

use Event;

class SlackButton extends WidgetBase
{
    protected $defaultAlias = 'slackButton';
    protected $data = null;

    public function render()
    {
        return $this->makePartial('slackbutton');
    }

    public function renderModal()
    {
        return $this->makePartial('slackbutton_modal');
    }

    public function onAuthorizeSlack()
    {
        return Slack::authorize($this->data);
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}
