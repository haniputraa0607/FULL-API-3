<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateFkInNewsFormDataDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('news_form_data_details', function(Blueprint $table) {
            $table->dropForeign('fk_news_form_data_details_form_datas');
            $table->foreign('id_news_form_data', 'fk_news_form_data_details_form_datas')->references('id_news_form_data')->on('news_form_datas')->onUpdate('CASCADE')->onDelete('CASCADE');
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
            $table->dropForeign('fk_news_form_data_details_form_datas');
            $table->foreign('id_news_form_data', 'fk_news_form_data_details_form_datas')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}
