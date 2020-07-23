<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingToMonthlyCustomerReportRegistrationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monthly_customer_report_registration', function (Blueprint $table) {
        	$table->index('reg_year');
        	$table->index('reg_month');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('monthly_customer_report_registration', function (Blueprint $table) {
        	$table->dropIndex(['reg_year']);
        	$table->dropIndex(['reg_month']);
        });
    }
}
