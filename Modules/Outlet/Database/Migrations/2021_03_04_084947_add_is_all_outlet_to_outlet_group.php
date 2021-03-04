<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsAllOutletToOutletGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_groups', function (Blueprint $table) {
            $table->smallInteger('is_all_outlet')->default(0)->after('outlet_group_type');
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
            $table->dropColumn('is_all_outlet');
        });
    }
}
