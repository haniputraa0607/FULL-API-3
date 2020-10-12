<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMinBasketSizeColumnToPromoCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
        	$table->integer('min_basket_size')->nullable()->after('charged_central');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
        	$table->dropColumn('min_basket_size');
        });
    }
}
