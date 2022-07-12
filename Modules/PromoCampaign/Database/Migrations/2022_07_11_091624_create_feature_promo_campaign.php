<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeaturePromoCampaign extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('featured_promo_campaigns', function (Blueprint $table) {
            $table->increments('id_featured_promo_campaign');
            $table->integer('id_promo_campaign')->unsigned();
            $table->dateTime('date_start');
            $table->dateTime('date_end');
            $table->unsignedInteger('order');
            $table->enum('feature_type', ['home', 'merchant'])->nullable();
            $table->timestamps();

            $table->foreign('id_promo_campaign', 'fk_promo_campaigns_featured_promo_campaigns')->references('id_promo_campaign')->on('promo_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('featured_promo_campaigns');
    }
}
