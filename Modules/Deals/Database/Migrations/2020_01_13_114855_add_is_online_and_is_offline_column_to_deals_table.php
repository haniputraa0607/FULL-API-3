<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsOnlineAndIsOfflineColumnToDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals', function (Blueprint $table) {
        	$table->boolean('is_online')->nullable()->after('deals_total_used');
        	$table->boolean('is_offline')->nullable()->after('is_online');
        	$table->boolean('step_complete')->nullable()->after('is_offline');
        	$table->dropColumn('deals_tos');
        	$table->dropColumn('deals_description');
        	$table->dropColumn('deals_short_description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals', function (Blueprint $table) {
        	$table->dropColumn('is_online');
        	$table->dropColumn('is_offline');
        	$table->dropColumn('step_complete');
			$table->longText('deals_tos')->nullable()->after('deals_total_used');
        	$table->text('deals_description', 65535)->nullable()->after('deals_second_title');
			$table->string('deals_short_description')->nullable()->after('deals_description');
        });
    }
}
