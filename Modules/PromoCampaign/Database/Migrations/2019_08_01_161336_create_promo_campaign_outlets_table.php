<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_outlets', function (Blueprint $table) {
            $table->increments('id_promo_campaign_outlet');
            $table->unsignedInteger('id_promo_campaign');
            $table->unsignedInteger('id_outlet');
            $table->timestamps();

            $table->foreign('id_promo_campaign', 'fk_promo_campaign_outlets_promo_campaign')->references('id_promo_campaign')->on('promo_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_promo_campaign_outlets_outlet')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_outlets');
    }
}
