<?php namespace Gency\Slack\Models;

use Model;

/**
 * Model
 */
class Verification extends Model
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
    public $table = 'gency_slack_verifications';

    public $fillable = [
        'data'
    ];

    public $casts = [
        'data' => 'json'
    ];
}
