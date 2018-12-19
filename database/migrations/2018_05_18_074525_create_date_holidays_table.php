<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDateHolidaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('date_holidays', function (Blueprint $table) {
            $table->increments('id_date_holiday');
            $table->integer('id_holiday')->unsigned();
            $table->date('date');
            $table->timestamps();

            $table->foreign('id_holiday', 'fk_date_holidays_holidays')->references('id_holiday')->on('holidays')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('date_holidays');
    }
}
