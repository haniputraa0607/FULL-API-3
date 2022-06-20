<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPassedStatusToUserHairStylist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_hair_stylist_documents', function (Blueprint $table) {
            $table->enum('conclusion_status', ['Passed', 'Not Passed'])->nullable()->after('attachment');
            $table->integer('conclusion_score')->nullable()->after('attachment');
        });
        Schema::table('user_hair_stylist_theories', function (Blueprint $table) {
            $table->enum('passed_status', ['Passed', 'Not Passed'])->nullable()->after('score');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_hair_stylist_documents', function (Blueprint $table) {
            $table->dropColumn('conclusion_status');
            $table->dropColumn('conclusion_score');
        });
        Schema::table('user_hair_stylist_theories', function (Blueprint $table) {
            $table->dropColumn('passed_status');
        });
    }
}
