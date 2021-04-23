<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAutoclaimToQuestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->boolean('autoclaim_quest')->after('is_complete')->default(0);
        });
        Schema::table('quest_benefits', function (Blueprint $table) {
            $table->boolean('autoclaim_benefit')->after('id_quest')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dropColumn('autoclaim_quest');
        });
        Schema::table('quest_benefits', function (Blueprint $table) {
            $table->dropColumn('autoclaim_benefit');
        });
    }
}
