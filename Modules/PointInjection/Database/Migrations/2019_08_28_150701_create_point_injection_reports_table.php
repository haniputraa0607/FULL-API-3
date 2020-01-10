<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointInjectionReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_injection_reports', function (Blueprint $table) {
            $table->increments('id_point_injection_report');
            $table->unsignedInteger('id_point_injection');
            $table->unsignedInteger('id_user');
            $table->integer('point');
            $table->timestamps();

            $table->foreign('id_point_injection', 'fk_point_injection_reports_id_point_injection')->references('id_point_injection')->on('point_injections')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_point_injection_reports_id_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('point_injection_reports');
    }
}
