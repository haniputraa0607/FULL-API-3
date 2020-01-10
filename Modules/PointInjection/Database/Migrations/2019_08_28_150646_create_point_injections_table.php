<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointInjectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_injections', function (Blueprint $table) {
            $table->increments('id_point_injection');
            $table->unsignedInteger('created_by');
            $table->string('title');
            $table->enum('send_type', ['One Time', 'Daily']);
            $table->date('start_date');
            $table->time('send_time');
            $table->integer('duration');
            $table->integer('point');
            $table->integer('total_point');
            $table->char('point_injection_media_push', 1)->default(0);
            $table->string('point_injection_push_subject')->nullable();
            $table->text('point_injection_push_content')->nullable();
            $table->string('point_injection_push_image')->nullable();
            $table->string('point_injection_push_clickto')->nullable();
            $table->string('point_injection_push_link')->nullable();
            $table->integer('point_injection_push_id_reference')->nullable();
            $table->timestamps();

            $table->foreign('created_by', 'fk_point_injections_created_by')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('point_injections');
    }
}
