<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOutletGroupToQuestDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quest_details', function (Blueprint $table) {
            $table->unsignedInteger('id_outlet_group')->after('id_outlet')->nullable();
            $table->foreign('id_outlet_group', 'fk_id_outlet_group_quest_details')->on('outlet_groups')->references('id_outlet_group')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quest_details', function (Blueprint $table) {
            $table->dropForeign('fk_id_outlet_group_quest_details');
            $table->dropColumn('id_outlet_group');
        });
    }
}
