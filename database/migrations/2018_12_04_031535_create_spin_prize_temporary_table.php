<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpinPrizeTemporaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spin_prize_temporary', function (Blueprint $table) {
            $table->increments('id_spin_prize_temporary');
            $table->integer('id_deals')->unsigned()->comment('temporary spin prize');
            $table->integer('id_user')->unsigned();
            $table->timestamps();

            $table->foreign('id_deals', 'fk_spin_temp_deals')->references('id_deals')->on('deals')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_spin_temp_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spin_prize_temporary');
    }
}
