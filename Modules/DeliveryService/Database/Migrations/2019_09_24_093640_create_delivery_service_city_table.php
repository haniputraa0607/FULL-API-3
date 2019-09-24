<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryServiceCityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_service_city', function (Blueprint $table) {
            $table->increments('id_delivery_service_city');
            $table->unsignedInteger('id_delivery_service');
            $table->unsignedInteger('id_city');
            $table->integer('phone_number')->nullable();
            $table->char('is_active', 1)->default(1);
            $table->timestamps();

            $table->foreign('id_delivery_service', 'fk_promo_delivery_service_city_delivery_service')->references('id_delivery_service')->on('delivery_services')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_city', 'fk_promo_delivery_service_city_city')->references('id_city')->on('cities')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_service_city');
    }
}
