<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTotalScoreToUserHairStylist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
            $table->integer('user_hair_stylist_score')->nullable()->after('user_hair_stylist_code');
            $table->enum('user_hair_stylist_passed_status', ['Passed', 'Not Passed'])->nullable()->after('user_hair_stylist_code');
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
            $table->dropColumn('user_hair_stylist_score');
            $table->dropColumn('user_hair_stylist_passed_status');
        });
    }
}
