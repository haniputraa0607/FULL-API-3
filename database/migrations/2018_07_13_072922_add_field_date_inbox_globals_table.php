<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldDateInboxGlobalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inbox_globals', function(Blueprint $table) {
			$table->dateTime('inbox_global_start')->nullable()->default(null)->after('inbox_global_content');
			$table->dateTime('inbox_global_end')->nullable()->default(null)->after('inbox_global_start');
			$table->enum('inbox_global_rulenya',['or','and'])->default('and')->after('inbox_global_end');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inbox_globals', function(Blueprint $table) {
            // $table->dropColumn('inbox_global_start');
            // $table->dropColumn('inbox_global_end');
            // $table->dropColumn('inbox_global_rulenya');
        });
    }
}
