<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserHairStylistDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_hair_stylist_devices', function (Blueprint $table) {
            $table->bigIncrements('id_user_hair_stylist_device');
            $table->unsignedBigInteger('id_user_hair_stylist');
            $table->enum('device_type', ['Android', 'IOS'])->nullable();
            $table->string('device_id');
            $table->string('device_token')->nullable();
            $table->timestamps();

            $table->foreign('id_user_hair_stylist')->on('user_hair_stylist')->references('id_user_hair_stylist')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_hair_stylist_devices');
    }
}
