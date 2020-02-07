<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdPromoCampaignToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedInteger('id_promo_campaign_promo_code')->nullable()->after('id_user');
            $table->foreign('id_promo_campaign_promo_code', 'fk_id_promo_campaign_promo_code_transactions_promo_campaigns')->references('id_promo_campaign_promo_code')->on('promo_campaign_promo_codes')->onUpdate('CASCADE')->onDelete('CASCADE');
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
            $table->dropForeign('fk_id_promo_campaign_promo_code_transactions_promo_campaigns');
            $table->dropColumn('id_promo_campaign_promo_code');
        });
    }
}
