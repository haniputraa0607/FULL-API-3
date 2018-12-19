<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropCampaignRuleCampaignsTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        Schema::table('campaigns', function(Blueprint $table)
        {
            $table->dropColumn('campaign_rule');
        });
    }

    public function down()
    {
        Schema::table('campaigns', function(Blueprint $table)
        {
            $table->enum('campaign_rule', array('or','and'))->default('and')->after('campaign_generate_receipient');
        });
    }
}
