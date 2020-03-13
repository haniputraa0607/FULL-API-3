<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestBenefitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quest_benefits', function (Blueprint $table) {
            $table->bigIncrements('id_quest_benefit');
            $table->bigInteger('id_quest')->unsigned();
            $table->enum('benefit_type', ['point', 'voucher']);
            $table->integer('value');
            $table->integer('id_deals')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('id_quest', 'fk_quest_benefits_id_quest')->references('id_quest')->on('quests')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_deals', 'fk_quest_benefits_id_deals')->references('id_deals')->on('deals')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quest_benefits');
    }
}
