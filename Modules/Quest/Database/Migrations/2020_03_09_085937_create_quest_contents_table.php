<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quest_contents', function (Blueprint $table) {
            $table->bigIncrements('id_quest_content');
            $table->bigInteger('id_quest')->unsigned();
            $table->string('title');
            $table->text('content');
            $table->integer('order');
            $table->timestamps();
            
            $table->foreign('id_quest', 'fk_quest_contents_id_quest')->references('id_quest')->on('quests')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quest_contents');
    }
}
