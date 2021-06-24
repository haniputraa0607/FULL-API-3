<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLimitUserPerDayToRulePromoPaymentGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rule_promo_payment_gateway', function (Blueprint $table) {
            $table->integer('limit_per_user_per_day')->after('limit_promo_total')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rule_promo_payment_gateway', function (Blueprint $table) {
            $table->dropColumn('limit_per_user_per_day');
        });
    }
}
