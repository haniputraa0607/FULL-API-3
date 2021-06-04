<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferenceIdToPromoPaymentGatewayValidationTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE promo_payment_gateway_validation_transactions MODIFY id_transaction INTEGER DEFAULT NULL;");
        Schema::table('promo_payment_gateway_validation_transactions', function (Blueprint $table) {
             $table->mediumText('reference_id')->nullable()->after('id_transaction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE promo_payment_gateway_validation_transactions MODIFY id_transaction INTEGER NOT NULL;");
        Schema::table('promo_payment_gateway_validation_transactions', function (Blueprint $table) {
            $table->dropColumn('reference_id');
        });
    }
}
