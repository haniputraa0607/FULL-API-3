<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletAppOtpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_app_otps', function (Blueprint $table) {
            $table->increments('id_outlet_app_otp');
            $table->boolean('used')->default(0);
            $table->string('feature');
            $table->string('pin');
            $table->unsignedInteger('id_user_outlet');
            $table->unsignedInteger('id_outlet');
            $table->timestamps();
            $table->foreign('id_outlet')->references('id_outlet')->on('outlets');
            $table->foreign('id_user_outlet')->references('id_user_outlet')->on('user_outlets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_app_otps');
    }
}
