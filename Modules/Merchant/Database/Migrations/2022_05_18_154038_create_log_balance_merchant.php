<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogBalanceMerchant extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_log_balances', function(Blueprint $table)
        {
            $table->integer('id_merchant_log_balance', true);
            $table->integer('id_merchant')->unsigned();
            $table->integer('merchant_balance')->default(0);
            $table->integer('merchant_balance_before')->default(0);
            $table->integer('merchant_balance_after')->default(0);
            $table->integer('merchant_balance_id_reference')->nullable();
            $table->string('merchant_balance_source', 191)->nullable();
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
        Schema::dropIfExists('merchant_log_balances');
    }
}
