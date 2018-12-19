<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpinTheWheelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spin_the_wheels', function (Blueprint $table) {
            $table->increments('id_spin_the_wheel');
            $table->integer('id_deals')->unsigned();
            $table->unsignedDecimal('value', 5, 2)->comment('weight value: between 1-100');
            $table->timestamps();

            $table->index(['id_deals'], 'fk_id_deals_idx');
            $table->foreign('id_deals', 'fk_id_deals_idx')
              ->references('id_deals')->on('deals')
              ->onUpdate('cascade')
              ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spin_the_wheels');
    }
}
