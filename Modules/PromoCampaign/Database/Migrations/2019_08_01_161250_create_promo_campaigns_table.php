<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaigns', function (Blueprint $table) {
            $table->increments('id_promo_campaign');
            $table->integer('created_by');
            $table->integer('last_updated_by');
            $table->string('campaign_name', 200);
            $table->string('promo_title', 200);
            $table->enum('code_type', ['Single', 'Multiple']);
            $table->string('prefix_code', 15);
            $table->integer('number_last_code');
            $table->integer('total_coupon');
            $table->dateTime('date_start');
            $table->dateTime('date_end');
            $table->char('is_all_outlet', 1);
            $table->enum('promo_type', ['Product discount', 'Tier discount', 'Buy X Get Y']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaigns');
    }
}
