<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignWhatsappSentContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_whatsapp_sent_contents', function (Blueprint $table) {
            $table->increments('id_campaign_whatsapp_sent_content');
            $table->unsignedInteger('id_campaign_whatsapp_sent');
            $table->enum('content_type',['text', 'image', 'file']);
            $table->text('content');
            $table->timestamps();

            $table->foreign('id_campaign_whatsapp_sent', 'fk_campaign_whatsapp_sent_contents_campaign_whatsapp_sents')
              ->references('id_campaign_whatsapp_sent')->on('campaign_whatsapp_sents')
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
        Schema::dropIfExists('campaign_whatsapp_sent_contents');
    }
}
