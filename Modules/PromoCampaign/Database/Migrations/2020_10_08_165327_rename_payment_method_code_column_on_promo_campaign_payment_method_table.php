<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenamePaymentMethodCodeColumnOnPromoCampaignPaymentMethodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_payment_methods', function(Blueprint $table) {
            $table->renameColumn('payment_method_code', 'payment_method');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaign_payment_methods', function(Blueprint $table) {
            $table->renameColumn('payment_method', 'payment_method_code');
        });
    }
}
