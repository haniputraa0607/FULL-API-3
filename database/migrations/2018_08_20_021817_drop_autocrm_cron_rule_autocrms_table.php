<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropAutocrmCronRuleAutocrmsTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        Schema::table('autocrms', function(Blueprint $table)
        {
            $table->dropColumn('autocrm_cron_rule');
        });
    }

    public function down()
    {
        Schema::table('autocrms', function(Blueprint $table)
        {
            $table->enum('autocrm_cron_rule', array('or','and'))->nullable()->after('autocrm_cron_reference');
        });
    }
}
