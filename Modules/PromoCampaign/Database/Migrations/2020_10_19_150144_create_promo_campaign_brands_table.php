<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_brands', function (Blueprint $table) {
        	$table->unsignedInteger('id_promo_campaign');
            $table->unsignedInteger('id_brand');

            $table->foreign('id_promo_campaign')->on('promo_campaigns')->references('id_promo_campaign')->onDelete('cascade');
            $table->foreign('id_brand')->on('brands')->references('id_brand')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_brands');
    }
}
