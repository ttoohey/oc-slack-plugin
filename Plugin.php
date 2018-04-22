<?php namespace Gency\Slack;

use Backend;
use System\Classes\PluginBase;

/**
 * Slack Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Slack',
            'description' => 'No description provided yet...',
            'author'      => 'Gency',
            'icon'        => 'icon-slack'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommand('Gency.Slack.Bot', Console\SlackBot::class);
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Gency\Slack\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'gency.slack.some_permission' => [
                'tab' => 'Slack',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'slack' => [
                'label'       => 'Slack',
                'url'         => Backend::url('gency/slack/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['gency.slack.*'],
                'order'       => 500,
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'keys' => [
                'label'       => 'Slack App',
                'description' => 'Manage Slack App.',
                'category'    => 'Bots',
                'icon'        => 'icon-slack',
                'class'       => Models\Settings::class,
                'order'       => 500,
                'keywords'    => 'slack bot'
            ]
        ];
    }
}
