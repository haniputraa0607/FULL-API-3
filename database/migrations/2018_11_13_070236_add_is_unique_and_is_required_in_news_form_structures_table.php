<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsUniqueAndIsRequiredInNewsFormStructuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('news_form_structures', function (Blueprint $table) {
            $table->boolean('is_required')->default(0)->after('form_input_autofill');
            $table->boolean('is_unique')->default(0)->after('is_required');
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
            $table->dropColumn('is_required');
            $table->dropColumn('is_unique');
        });
    }
}
