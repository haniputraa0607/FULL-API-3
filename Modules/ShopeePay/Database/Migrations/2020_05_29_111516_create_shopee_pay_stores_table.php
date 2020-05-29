<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopeePayStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopee_pay_stores', function (Blueprint $table) {
            $table->bigIncrements('id_shopee_pay_store');
            $table->unsignedInteger('id_outlet')->nullable();
            $table->string('store_name')->nullable();
            $table->string('store_id')->nullable();
            $table->string('merchant_host_id')->nullable();
            $table->string('merchant_ext_id')->nullable();
            $table->string('store_ext_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('district')->nullable();
            $table->string('ward')->nullable();
            $table->text('address')->nullable();
            $table->string('gps_longitude')->nullable();
            $table->string('gps_latitude')->nullable();
            $table->string('terminal_ids')->nullable();
            $table->string('mcc')->nullable();
            $table->string('nmid')->nullable();
            $table->string('merchant_criteria')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('point_of_initiation')->nullable();
            $table->text('settlement_emails')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();

            $table->foreign('id_outlet')->references('id_outlet')->on('outlets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shopee_pay_stores');
    }
}
