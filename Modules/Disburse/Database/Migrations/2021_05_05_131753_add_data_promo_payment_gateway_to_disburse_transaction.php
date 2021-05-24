<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDataPromoPaymentGatewayToDisburseTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_outlet_transactions', function (Blueprint $table) {
            $table->decimal('payment_charge_old', 30, 4)->nullable()->default(0)->after('payment_charge');
            $table->decimal('expense_central_old', 30, 4)->nullable()->default(0)->after('expense_central');
            $table->decimal('income_outlet_old', 30, 4)->nullable()->default(0)->after('income_outlet');
            $table->decimal('income_central_old', 30, 4)->nullable()->default(0)->after('income_central');
            $table->decimal('charged_promo_payment_gateway_outlet', 30,4)->nullable()->default(0)->after('charged_subscription_central');
            $table->decimal('charged_promo_payment_gateway_central', 30,4)->nullable()->default(0)->after('charged_subscription_central');
            $table->decimal('charged_promo_payment_gateway', 30,4)->nullable()->default(0)->after('charged_subscription_central');
            $table->decimal('fee_promo_payment_gateway_outlet', 30,4)->nullable()->default(0)->after('charged_subscription_central');
            $table->decimal('fee_promo_payment_gateway_central', 30,4)->nullable()->default(0)->after('charged_subscription_central');
            $table->decimal('fee_promo_payment_gateway', 30,4)->nullable()->default(0)->after('charged_subscription_central');
            $table->enum('fee_promo_payment_gateway_type', ['Percent', 'Nominal'])->nullable()->after('charged_subscription_central');
            $table->unsignedInteger('id_rule_promo_payment_gateway')->nullable()->after('charged_subscription_central');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disburse_outlet_transactions', function (Blueprint $table) {
            $table->dropColumn('income_outlet_old');
            $table->dropColumn('charged_promo_payment_gateway_outlet');
            $table->dropColumn('charged_promo_payment_gateway_central');
            $table->dropColumn('charged_promo_payment_gateway');
            $table->dropColumn('fee_promo_payment_gateway_outlet');
            $table->dropColumn('fee_promo_payment_gateway_central');
            $table->dropColumn('fee_promo_payment_gateway');
            $table->dropColumn('fee_promo_payment_gateway_type');
            $table->dropColumn('id_rule_promo_payment_gateway');
        });
    }
}
