<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAutocrmWhatsappLogContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('autocrm_whatsapp_log_contents', function (Blueprint $table) {
            $table->increments('id_autocrm_whatsapp_log_content');
            $table->unsignedInteger('id_autocrm_whatsapp_log');
            $table->enum('content_type',['text', 'image', 'file']);
            $table->text('content');
            $table->timestamps();

            $table->foreign('id_autocrm_whatsapp_log', 'fk_autocrm_whatsapp_log_contents_autocrm_whatsapp_logs')
              ->references('id_autocrm_whatsapp_log')->on('autocrm_whatsapp_logs')
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
        Schema::dropIfExists('autocrm_whatsapp_log_contents');
    }
}
