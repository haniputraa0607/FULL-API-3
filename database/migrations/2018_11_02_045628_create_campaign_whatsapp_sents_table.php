<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignWhatsappSentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_whatsapp_sents', function (Blueprint $table) {
            $table->increments('id_campaign_whatsapp_sent');
			$table->unsignedInteger('id_campaign');
			$table->string('whatsapp_sent_to');
			$table->dateTime('whatsapp_sent_send_at')->nullable();
            $table->timestamps();
            
            $table->foreign('id_campaign', 'fk_campaign_whatsapp_sents_campaigns')
              ->references('id_campaign')->on('campaigns')
              ->onUpdate('cascade')
              ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_whatsapp_sents');
    }
}
