<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPercentHairStylistGroupCommissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('hairstylist_group_commissions', function (Blueprint $table) {
            $table->boolean('percent')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('hairstylist_group_commissions', function (Blueprint $table) {
            $table->dropColumn('percent')->default(0);
        });
    }
}
