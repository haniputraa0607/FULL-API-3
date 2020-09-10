<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopeePayMerchantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopee_pay_merchants', function (Blueprint $table) {
            $table->bigIncrements('id_shopee_pay_merchant');
            $table->string('merchant_name')->nullable();
            $table->string('merchant_host_id')->nullable();
            $table->string('merchant_ext_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('district')->nullable();
            $table->string('ward')->nullable();
            $table->text('address')->nullable();
            $table->string('business_tax_id')->nullable();
            $table->string('national_id_type')->nullable();
            $table->string('national_id')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('mcc')->nullable();
            $table->string('point_of_initiation')->nullable();
            $table->text('settlement_emails')->nullable();
            $table->string('withdrawal_option')->nullable();
            $table->string('status')->nullable();
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
        Schema::dropIfExists('shopee_pay_merchants');
    }
}
