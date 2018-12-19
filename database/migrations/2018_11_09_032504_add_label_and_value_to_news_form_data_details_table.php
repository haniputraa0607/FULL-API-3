<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLabelAndValueToNewsFormDataDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('news_form_data_details', function(Blueprint $table) {
            $table->string('form_input_label')->after('id_news');
            $table->string('form_input_value')->nullable()->after('form_input_label');
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
            $table->dropColumn('form_input_label');
            $table->dropColumn('form_input_value');
        });
    }
}
