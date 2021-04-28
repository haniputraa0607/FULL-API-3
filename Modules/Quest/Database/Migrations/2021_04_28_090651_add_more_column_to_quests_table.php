<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMoreColumnToQuestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dateTime('stop_at')->after('date_end')->nullable();
            $table->string('stop_reason')->after('stop_at')->nullable();
            $table->unsignedInteger('quest_limit')->after('stop_reason')->default(0);
            $table->unsignedInteger('quest_claimed')->after('quest_limit')->default(0);
            $table->unsignedInteger('benefit_claimed')->after('quest_claimed')->default(0);
            $table->unsignedInteger('max_complete_day')->after('date_end')->nullable();
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
            $table->dropColumn('stop_at');
            $table->dropColumn('stop_reason');
            $table->dropColumn('quest_limit');
            $table->dropColumn('quest_claimed');
            $table->dropColumn('benefit_claimed');
            $table->dropColumn('max_complete_day');
        });
    }
}
