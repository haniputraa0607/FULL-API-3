<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyMembershipReportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_membership_report', function (Blueprint $table) {
            $table->increments('id_daily_membership');
			$table->date('mem_date')->nullable();
			$table->integer('id_membership')->nullable();
			$table->integer('cust_total')->nullable();
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
        Schema::dropIfExists('daily_membership_report');
    }
}
