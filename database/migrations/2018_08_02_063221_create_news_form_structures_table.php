<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewsFormStructuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news_form_structures', function (Blueprint $table) {
            $table->increments('id_news_form_structure');
            $table->unsignedInteger('id_news');
            $table->enum('form_input_types',['Short Text','Long Text','Number Input','Date','Date & Time','Dropdown Choice','Radio Button Choice','Multiple Choice','Image Upload','File Upload'])->default('Short Text');
            $table->text('form_input_options')->nullable();
            $table->string('form_input_label',30);
            $table->timestamps();
			
			$table->foreign('id_news', 'fk_news_form_structures_news')->references('id_news')->on('news')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
		
		
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		Schema::table('news_form_structures', function(Blueprint $table) {
			$table->dropForeign('fk_news_form_structures_news');
        });
		
		Schema::dropIfExists('fk_news_form_structures_news');
    }
}
