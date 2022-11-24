<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateForeigKeyTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign('fk_id_promo_campaign_promo_code_transactions_promo_campaigns');
            $table->dropForeign('fk_id_subscription_user_vouchers_transactions');
            $table->dropForeign('fk_transactions_outlets');
            $table->dropForeign('fk_transactions_users');

            $table->foreign('id_promo_campaign_promo_code', 'fk_id_promo_campaign_promo_code_transactions_promo_campaigns')->references('id_promo_campaign_promo_code')->on('promo_campaign_promo_codes')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('id_subscription_user_voucher', 'fk_id_subscription_user_vouchers_transactions')
                ->references('id_subscription_user_voucher')->on('subscription_user_vouchers')
                ->onDelete('RESTRICT')
                ->onUpdate('RESTRICT');
            $table->foreign('id_outlet', 'fk_transactions_outlets')->references('id_outlet')->on('outlets')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('id_user', 'fk_transactions_users')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('id_promo_campaign_promo_code', 'fk_id_promo_campaign_promo_code_transactions_promo_campaigns')->references('id_promo_campaign_promo_code')->on('promo_campaign_promo_codes')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_subscription_user_voucher', 'fk_id_subscription_user_vouchers_transactions')
                ->references('id_subscription_user_voucher')->on('subscription_user_vouchers')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
            $table->foreign('id_outlet', 'fk_transactions_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_transactions_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}
