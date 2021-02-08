<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdFilterParentOutletGroupFilterCondition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_group_filter_conditions', function (Blueprint $table) {
            $table->unsignedInteger('id_outlet_group_filter_condition_parent')->after('id_outlet_group_filter_condition');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_group_filter_conditions', function (Blueprint $table) {
            $table->dropColumn('id_outlet_group_filter_condition_parent');
        });
    }
}
