<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->increments('id_user_notification');
            $table->unsignedInteger('id_user');
            $table->integer('inbox')->default(0);
            $table->integer('voucher')->default(0);
            $table->integer('history')->default(0);

            $table->foreign('id_user', 'fk_user_notifications_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
                    
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
        Schema::dropIfExists('user_notifications');
    }
}
