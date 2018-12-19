<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromotionContentShortenLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotion_content_shorten_links', function (Blueprint $table) {
            $table->increments('id_promotion_content_shorten_link');
            $table->unsignedInteger('id_promotion_content');
            $table->string('original_link');
            $table->string('short_link');
            $table->enum('type', ['email', 'sms', 'push_notification', 'inbox']);
            $table->timestamps();

            $table->foreign('id_promotion_content', 'fk_promotion_content_shorten_links_promotion_contents')->references('id_promotion_content')->on('promotion_contents')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promotion_content_shorten_links');
    }
}
