<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserFraudsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_frauds', function (Blueprint $table) {
            $table->bigIncrements('id_user_fraud');
            $table->unsignedInteger('id_user');
			$table->enum('device_type', array('Android','IOS'))->nullable();
			$table->string('device_id', 20);
            $table->timestamps();

            $table->foreign('id_user', 'fk_custom_page_images_id_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_frauds');
    }
}
