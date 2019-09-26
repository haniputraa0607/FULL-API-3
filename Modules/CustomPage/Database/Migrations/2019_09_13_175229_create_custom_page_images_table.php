<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomPageImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_page_images', function (Blueprint $table) {
            $table->increments('id_custom_page_image');
            $table->unsignedInteger('id_custom_page');
            $table->string('custom_page_image', 255)->nullable();
            $table->timestamps();

            $table->foreign('id_custom_page', 'fk_custom_page_images_id_custom_page')->references('id_custom_page')->on('custom_pages')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_page_images');
    }
}
