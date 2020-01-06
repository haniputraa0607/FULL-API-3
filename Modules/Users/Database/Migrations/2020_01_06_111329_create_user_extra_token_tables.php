<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserExtraTokenTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_extra_token', function (Blueprint $table) {
            $table->bigIncrements('id_user_extra_token');
            $table->unsignedInteger('id_user');
            $table->longText('extra_token')->nullable();
            $table->timestamps();
            
            $table->foreign('id_user', 'fk_user_extra_token_id_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_extra_token');
    }
}
