<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBalanceToUserHairStylistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
        	$table->integer('total_balance')->default(0)->after('total_rating');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
        	$table->dropColumn('total_balance');
        });
    }
}
