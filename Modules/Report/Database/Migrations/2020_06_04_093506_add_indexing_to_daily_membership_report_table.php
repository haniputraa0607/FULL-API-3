<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingToDailyMembershipReportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_membership_report', function (Blueprint $table) {
        	$table->index('id_membership');
        	$table->index('mem_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('daily_membership_report', function (Blueprint $table) {
        	$table->dropIndex(['id_membership']);
        	$table->dropIndex(['mem_date']);
        });
    }
}
