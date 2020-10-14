<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignShipmentMethodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_shipment_methods', function (Blueprint $table) {
            $table->increments('id_promo_campaign_shipment_method');
            $table->unsignedInteger('id_promo_campaign');
            $table->string('shipment_method');
            $table->timestamps();

            $table->foreign('id_promo_campaign', 'fk_promo_campaign_shipment_methods_promo_campaign')->references('id_promo_campaign')->on('promo_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_shipment_method');
    }
}
