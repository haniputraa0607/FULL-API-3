<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropInboxGlobalRulenyaInboxGlobalsTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        Schema::table('inbox_globals', function(Blueprint $table)
        {
            $table->dropColumn('inbox_global_rulenya');
        });
    }

    public function down()
    {
        Schema::table('inbox_globals', function(Blueprint $table)
        {
            $table->enum('inbox_global_rulenya', array('or','and'))->default('and')->after('inbox_global_end');
        });
    }
}
