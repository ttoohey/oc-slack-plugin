<?php namespace Gency\Slack\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateGencySlackTeams extends Migration
{
    public function up()
    {
        Schema::create('gency_slack_teams', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('access_token');
            $table->text('scope');
            $table->text('team_name');
            $table->text('team_id');
            $table->text('incoming_webhook')->nullable();
            $table->text('bot')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('gency_slack_teams');
    }
}