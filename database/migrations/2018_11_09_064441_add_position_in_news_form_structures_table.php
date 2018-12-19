<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPositionInNewsFormStructuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('news_form_structures', function (Blueprint $table) {
            $table->integer('position')->nullable()->after('form_input_autofill');
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
            $table->dropColumn('position');
        });
    }
}
