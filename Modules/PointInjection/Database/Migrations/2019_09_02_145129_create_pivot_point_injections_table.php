<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePivotPointInjectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pivot_point_injections', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_point_injection');
            $table->unsignedInteger('id_user');
            $table->string('title');
            $table->dateTime('send_time');
            $table->integer('point');
            $table->char('point_injection_media_push', 1)->default(0);
            $table->string('point_injection_push_subject')->nullable();
            $table->text('point_injection_push_content')->nullable();
            $table->string('point_injection_push_image')->nullable();
            $table->string('point_injection_push_clickto')->nullable();
            $table->string('point_injection_push_link')->nullable();
            $table->integer('point_injection_push_id_reference')->nullable();
            $table->timestamps();

            $table->foreign('id_point_injection', 'fk_pivot_point_injections_id_point_injection')->references('id_point_injection')->on('point_injections')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_pivot_point_injections_id_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pivot_point_injection');
    }
}
