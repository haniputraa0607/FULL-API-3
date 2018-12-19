<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewsFormDataDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news_form_data_details', function (Blueprint $table) {
            $table->increments('id_news_form_data_detail');
            $table->unsignedInteger('id_news_form_data');
            $table->unsignedInteger('id_news');
            $table->timestamps();
			
			$table->foreign('id_news', 'fk_news_form_data_details_news')->references('id_news')->on('news')->onUpdate('CASCADE')->onDelete('CASCADE');
		
			$table->foreign('id_news_form_data', 'fk_news_form_data_details_form_datas')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		Schema::table('news_form_data_details', function(Blueprint $table) {
			$table->dropForeign('fk_news_form_data_details_news');
		$table->dropForeign('fk_news_form_data_details_form_datas');
        });
		
        Schema::dropIfExists('news_form_data_details');
    }
}
