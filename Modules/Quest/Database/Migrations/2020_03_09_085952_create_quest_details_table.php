<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quest_details', function (Blueprint $table) {
            $table->bigIncrements('id_quest_detail');
            $table->bigInteger('id_quest')->unsigned();
            $table->string('name');
            $table->integer('id_product')->unsigned()->nullable();
            $table->integer('product_total')->nullable();
            $table->integer('trx_nominal')->nullable();
            $table->integer('trx_total')->nullable();
            $table->integer('id_outlet')->unsigned()->nullable();
            $table->integer('id_province')->unsigned()->nullable();
            $table->integer('different_outlet')->nullable();
            $table->integer('different_province')->nullable();
            $table->timestamps();

            $table->foreign('id_quest', 'fk_quest_details_id_quest')->references('id_quest')->on('quests')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_product', 'fk_quest_details_id_product')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_quest_details_id_outlet')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_province', 'fk_quest_details_id_province')->references('id_province')->on('provinces')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quest_details');
    }
}
