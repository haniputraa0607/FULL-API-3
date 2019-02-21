<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyCustomerReportRegistrationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_customer_report_registration', function (Blueprint $table) {
            $table->increments('id_daily_customer_report_registration');
            $table->date('reg_date')->nullable();
			$table->integer('cust_male')->nullable();
            $table->integer('cust_female')->nullable();
            $table->integer('cust_android')->nullable();
            $table->integer('cust_ios')->nullable();
            $table->integer('cust_telkomsel')->nullable();
            $table->integer('cust_xl')->nullable();
            $table->integer('cust_indosat')->nullable();
            $table->integer('cust_tri')->nullable();
            $table->integer('cust_axis')->nullable();
            $table->integer('cust_smart')->nullable();
            $table->integer('cust_teens')->nullable();
            $table->integer('cust_young_adult')->nullable();
            $table->integer('cust_adult')->nullable();
            $table->integer('cust_old')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daily_customer_report_registration');
    }
}
