<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeFieldInboxContentToLongText extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        Schema::table('autocrms', function(Blueprint $table)
        {
            $table->longText('autocrm_inbox_content')->nullable()->change();
        });
        Schema::table('campaigns', function(Blueprint $table)
        {
            $table->longText('campaign_inbox_content')->nullable()->change();
        });
        Schema::table('promotion_contents', function(Blueprint $table)
        {
            $table->longText('promotion_inbox_content')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('autocrms', function(Blueprint $table)
        {
            $table->text('autocrm_inbox_content')->nullable()->change();
        });
        Schema::table('campaigns', function(Blueprint $table)
        {
            $table->text('campaign_inbox_content')->nullable()->change();
        });
        Schema::table('promotion_contents', function(Blueprint $table)
        {
            $table->text('promotion_inbox_content')->nullable()->change();
        });
    }
}
