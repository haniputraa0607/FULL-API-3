<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveOutletGroupFilterRule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_groups', function (Blueprint $table) {
            $table->dropColumn('outlet_group_filter_rule');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_groups', function (Blueprint $table) {
            $table->enum('outlet_group_filter_rule', ['and', 'or'])->nullable();
        });
    }
}
