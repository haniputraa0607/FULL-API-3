<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMerchantInboxes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_inboxes', function (Blueprint $table) {
            $table->bigIncrements('id_merchant_inboxes');
            $table->integer('id_campaign')->unsigned()->nullable();
            $table->integer('id_merchant')->unsigned();
            $table->string('inboxes_subject', 191);
            $table->text('inboxes_content', 65535)->nullable();
            $table->string('inboxes_clickto', 191);
            $table->string('inboxes_link')->nullable();
            $table->string('inboxes_id_reference', 20)->nullable();
            $table->dateTime('inboxes_send_at')->nullable();
            $table->char('read', 1)->default(0);
            $table->integer('id_brand')->unsigned()->nullable();
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
        Schema::dropIfExists('merchant_inboxes');
    }
}
