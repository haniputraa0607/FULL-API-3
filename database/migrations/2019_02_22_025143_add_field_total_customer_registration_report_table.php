<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldTotalCustomerRegistrationReportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_customer_report_registration', function (Blueprint $table) {
            $table->integer('total')->nullable()->after('reg_date');
        });
		
		Schema::table('monthly_customer_report_registration', function (Blueprint $table) {
            $table->integer('total')->nullable()->after('reg_year');
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
            $table->dropColumn('total');
        });
		
		Schema::table('monthly_customer_report_registration', function (Blueprint $table) {
            $table->dropColumn('total');
        });
    }
}
