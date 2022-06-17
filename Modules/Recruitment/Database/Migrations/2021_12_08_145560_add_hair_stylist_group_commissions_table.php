<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHairStylistGroupCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_group_commissions', function (Blueprint $table) {
            $table->increments('id_hairstylist_group_commission');
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
            $table->dropColumn('id_hairstylist_group_commission');
        });
    }
}
