<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingToDailyCustomerReportRegistrationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_customer_report_registration', function (Blueprint $table) {
        	$table->index('reg_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('daily_customer_report_registration', function (Blueprint $table) {
        	$table->dropIndex(['reg_date']);
        });
    }
}
