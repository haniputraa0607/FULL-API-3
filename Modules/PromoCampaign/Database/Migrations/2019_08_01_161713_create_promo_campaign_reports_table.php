<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_reports', function (Blueprint $table) {
            $table->increments('id_promo_campaign_report');
            $table->unsignedInteger('id_promo_campaign_promo_code');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_outlet');
            $table->string('device_id', 200);
            $table->enum('device_type', ['Android', 'IOS']);
            $table->string('user_name', 200);
            $table->string('user_phone', 200);
            $table->timestamps();

            $table->foreign('id_promo_campaign_promo_code', 'fk_promo_campaign_reports_promo_campaign_promo_code')->references('id_promo_campaign_promo_code')->on('promo_campaign_promo_codes')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_promo_campaign_reports_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_transaction', 'fk_promo_campaign_reports_transaction')->references('id_transaction')->on('transactions')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_promo_campaign_reports_outlet')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_reports');
    }
}
