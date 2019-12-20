<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropPromoCampaignUserFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('promo_campaign_user_filters');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('promo_campaign_user_filters', function (Blueprint $table) {
            $table->increments('id_promo_campaign_user_filter');
            $table->string('subject', 200);
            $table->char('operator', 3);
            $table->string('parameter', 200);
            $table->timestamps();
        });
    }
}
