<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogTopupPosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_topup_pos', function (Blueprint $table) {
            $table->increments('id_log_topup_pos');
            $table->unsignedInteger('id_log_topup')->foreign('id_log_topup', 'fk_log_topup_log_topup_pos')
                            ->references('id_log_topup')->on('log_topups')
                            ->onDelete('cascade')
                            ->onUpdate('cascade');
            $table->unsignedInteger('id_outlet')->nullable();
            $table->string('otp')->nullable();
            $table->string('status')->default('Pending');
            $table->dateTime('expired_at')->nullable();
            $table->timestamps();

            $table->foreign('id_outlet', 'fk_outlet_log_topup_pos')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('log_topup_pos');
    }
}
