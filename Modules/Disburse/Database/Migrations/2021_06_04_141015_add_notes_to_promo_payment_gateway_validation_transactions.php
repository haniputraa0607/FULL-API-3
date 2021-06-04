<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotesToPromoPaymentGatewayValidationTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_payment_gateway_validation_transactions', function (Blueprint $table) {
            $table->mediumText('notes')->nullable()->after('old_cashback');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_payment_gateway_validation_transactions', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
}
