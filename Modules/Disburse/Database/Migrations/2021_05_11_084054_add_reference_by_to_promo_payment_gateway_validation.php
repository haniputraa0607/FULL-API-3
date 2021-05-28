<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferenceByToPromoPaymentGatewayValidation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_payment_gateway_validation', function (Blueprint $table) {
            $table->enum('reference_by', ['transaction_receipt_number', 'id_payment'])->nullable()->after('id_rule_promo_payment_gateway');
            $table->enum('processing_status', ['In Progress', 'Success', 'Fail'])->nullable()->after('id_rule_promo_payment_gateway');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_payment_gateway_validation', function (Blueprint $table) {
            $table->dropColumn('reference_by');
            $table->dropColumn('processing_status');
        });
    }
}
