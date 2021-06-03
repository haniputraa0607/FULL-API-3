<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddValidationCashbackType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_payment_gateway_validation', function (Blueprint $table) {
            $table->enum('validation_cashback_type', ['Check Cashback', 'Not Check Cashback'])->nullable()->default('Check Cashback')->after('reference_by');
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
            $table->dropColumn('validation_cashback_type');
        });
    }
}
