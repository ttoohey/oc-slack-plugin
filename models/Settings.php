<?php namespace Gency\Slack\Models;

use Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
    public $settingsCode = 'gency_slack_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';
}
