<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDashboardUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dashboard_users', function (Blueprint $table) {
            $table->increments('id_dashboard_user');
            $table->unsignedInteger('id_user');
            $table->string('section_title');
            $table->smallInteger('section_order');
            $table->timestamps();

            $table->foreign('id_user', 'fk_dashboard_users_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dashboard_users');
    }
}
