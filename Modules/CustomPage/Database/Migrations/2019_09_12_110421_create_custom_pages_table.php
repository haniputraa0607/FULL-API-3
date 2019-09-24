<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomPagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_pages', function (Blueprint $table) {
            $table->increments('id_custom_page');
            $table->string('custom_page_title', 35);
            $table->longText('custom_page_description');
            $table->smallInteger('custom_page_order')->nullable()->default(0);
            $table->string('custom_page_icon_image', 255)->nullable();
            $table->string('custom_page_video_text', 255)->nullable();
            $table->string('custom_page_video', 255)->nullable();
            $table->date('custom_page_event_date_start')->nullable();
            $table->date('custom_page_event_date_end')->nullable();
            $table->time('custom_page_event_time_start')->nullable();
            $table->time('custom_page_event_time_end')->nullable();
            $table->string('custom_page_event_location_name', 150)->nullable();
            $table->string('custom_page_event_location_phone', 100)->nullable();
            $table->text('custom_page_event_location_address')->nullable();
            $table->string('custom_page_event_location_map', 200)->nullable();
            $table->string('custom_page_event_latitude', 25)->nullable();
            $table->string('custom_page_event_longitude', 25)->nullable();
            $table->string('custom_page_outlet_text', 150)->nullable();
            $table->string('custom_page_product_text', 150)->nullable();
            $table->char('custom_page_button_form', 1)->default(0)->nullable();
            $table->string('custom_page_button_form_text', 50)->nullable();
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
        Schema::dropIfExists('custom_pages');
    }
}
