<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLimitAdditionalPromoPg extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rule_promo_payment_gateway', function (Blueprint $table) {
            $table->dropColumn('limit_promo_additional');
            $table->dropColumn('limit_promo_additional_type');
            $table->integer('limit_promo_additional_account')->nullable()->after('limit_promo_total');
            $table->integer('limit_promo_additional_month')->nullable()->after('limit_promo_total');
            $table->integer('limit_promo_additional_week')->nullable()->after('limit_promo_total');
            $table->integer('limit_promo_additional_day')->nullable()->after('limit_promo_total');
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
            $table->integer('limit_promo_additional')->nullable();
            $table->enum('limit_promo_additional_type', ['day', 'week', 'month', 'account'])->nullable();
            $table->dropColumn('limit_promo_additional_day');
            $table->dropColumn('limit_promo_additional_week');
            $table->dropColumn('limit_promo_additional_month');
            $table->dropColumn('limit_promo_additional_account');
        });
    }
}
