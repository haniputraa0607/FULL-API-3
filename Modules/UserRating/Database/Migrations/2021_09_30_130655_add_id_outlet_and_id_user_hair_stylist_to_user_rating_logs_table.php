<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOutletAndIdUserHairStylistToUserRatingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_rating_logs', function (Blueprint $table) {
        	$table->unsignedBigInteger('id_doctor')->nullable()->after('id_transaction');
        	$table->integer('id_outlet')->nullable()->after('id_doctor');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_rating_logs', function (Blueprint $table) {
        	$table->dropColumn('id_user_hair_stylist');
        	$table->drop('id_outlet');
        });
    }
}
