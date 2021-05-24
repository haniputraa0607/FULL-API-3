<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAmountToPromoPaymentGatewayTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_payment_gateway_transactions', function (Blueprint $table) {
            $table->integer('amount')->default(0)->after('id_transaction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_payment_gateway_transactions', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }
}
