<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRatingOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rating_options', function (Blueprint $table) {
            $table->increments('id_rating_option');
            $table->enum('rule_operator',['<','<=','>','>=','=']);
            $table->unsignedInteger('value');
            $table->string('question');
            $table->string('options');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rating_options');
    }
}
