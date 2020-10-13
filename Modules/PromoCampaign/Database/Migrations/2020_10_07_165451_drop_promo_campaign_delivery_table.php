<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropPromoCampaignDeliveryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('promo_campaign_delivery');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('promo_campaign_delivery', function (Blueprint $table) {
            $table->increments('id_promo_campaign_delivery');
            $table->unsignedInteger('id_promo_campaign');
            $table->string('delivery');
            $table->timestamps();

            $table->foreign('id_promo_campaign', 'fk_promo_campaign_deliverys_promo_campaign')->references('id_promo_campaign')->on('promo_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}
