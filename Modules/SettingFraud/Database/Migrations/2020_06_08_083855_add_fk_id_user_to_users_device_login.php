<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFkIdUserToUsersDeviceLogin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users_device_login', function(Blueprint $table)
        {
            $table->foreign('id_user', 'fk_users_device_login_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users_device_login', function(Blueprint $table)
        {
            $table->dropForeign('fk_users_device_login_users');
        });
    }
}
