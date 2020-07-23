<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingToMonthlyMembershipReportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monthly_membership_report', function (Blueprint $table) {
        	$table->index('id_membership');
        	$table->index('mem_year');
        	$table->index('mem_month');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('monthly_membership_report', function (Blueprint $table) {
        	$table->dropIndex(['id_membership']);
        	$table->dropIndex(['mem_year']);
        	$table->dropIndex(['mem_month']);
        });
    }
}
