<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdHairstylistTimeOffToHairStylistNotAvailable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_not_available', function (Blueprint $table) {
            $table->integer('id_hairstylist_time_off')->after('id_user_hair_stylist')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_not_available', function (Blueprint $table) {
            $table->dropColumn('id_hairstylist_time_off');
        });
    }
}
