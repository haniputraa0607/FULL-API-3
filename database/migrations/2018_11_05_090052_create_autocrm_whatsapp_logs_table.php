<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAutocrmWhatsappLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('autocrm_whatsapp_logs', function (Blueprint $table) {
            $table->increments('id_autocrm_whatsapp_log');
			$table->integer('id_user')->unsigned()->nullable();
			$table->string('whatsapp_log_to');
            $table->timestamps();
            
            $table->foreign('id_user', 'fk_autocrm_whatsapp_logs_autocrms')
            ->references('id')->on('users')
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
        Schema::dropIfExists('autocrm_whatsapp_logs');
    }
}
