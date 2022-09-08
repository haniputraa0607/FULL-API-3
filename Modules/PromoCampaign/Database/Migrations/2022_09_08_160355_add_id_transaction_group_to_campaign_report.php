<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdTransactionGroupToCampaignReport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_reports', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->nullable()->after('id_transaction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaign_reports', function (Blueprint $table) {
            $table->dropColumn('id_transaction_group');
        });
    }
}
