<?php namespace Gency\Slack\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateGencySlackVerifications extends Migration
{
    public function up()
    {
        Schema::create('gency_slack_verifications', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->json('data');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('gency_slack_verifications');
    }
}