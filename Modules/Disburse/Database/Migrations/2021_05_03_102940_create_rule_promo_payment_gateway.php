<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRulePromoPaymentGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rule_promo_payment_gateway', function (Blueprint $table) {
            $table->integerIncrements('id_rule_promo_payment_gateway');
            $table->string('promo_payment_gateway_code', 100)->unique();
            $table->string('name', 200);
            $table->string('payment_gateway');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('limit_promo_total')->nullable();
            $table->integer('limit_promo_additional')->nullable();
            $table->enum('limit_promo_additional_type', ['day', 'week', 'month', 'account'])->nullable();
            $table->enum('limit_promo_additional_account_type', ['Jiwa+', 'Payment Gateway'])->nullable();
            $table->enum('cashback_type', ['Percent', 'Nominal']);
            $table->decimal('cashback', 30,2)->default(0);
            $table->integer('maximum_cashback')->default(0);
            $table->integer('minimum_transaction')->default(0);
            $table->enum('charged_type', ['Percent', 'Nominal']);
            $table->decimal('charged_payment_gateway', 30,2)->default(0);
            $table->decimal('charged_jiwa_group', 30,2)->default(0);
            $table->decimal('charged_central', 30,2)->default(0);
            $table->decimal('charged_outlet', 30,2)->default(0);
            $table->enum('mdr_setting', ['Total Amount PG', 'Total Amount PG - Cashback Jiwa Group', 'Total Amount PG - Total Cashback Customer'])->nullable()->default('Total Amount PG');
            $table->smallInteger('start_status')->default(0);
            $table->unsignedInteger('last_updated_by')->nullable();
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
        Schema::dropIfExists('rule_promo_payment_gateway');
    }
}
