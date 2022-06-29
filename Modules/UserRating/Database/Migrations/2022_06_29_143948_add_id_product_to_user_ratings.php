<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdProductToUserRatings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_ratings', function (Blueprint $table) {
            $table->integer('id_product')->after('id_outlet')->nullable();
        });
        Schema::table('user_rating_summaries', function (Blueprint $table) {
            $table->integer('id_product')->after('id_outlet')->nullable();
        });
        Schema::table('user_rating_logs', function (Blueprint $table) {
            $table->integer('id_product')->after('id_outlet')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_ratings', function (Blueprint $table) {
            $table->dropColumn('id_product');
        });
        Schema::table('user_rating_summaries', function (Blueprint $table) {
            $table->dropColumn('id_product');
        });
        Schema::table('user_rating_logs', function (Blueprint $table) {
            $table->dropColumn('id_product');
        });
    }
}
