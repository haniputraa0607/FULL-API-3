<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateIndexingToSomePromotionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promotions', function (Blueprint $table) {
        	$table->index('promotion_name');
        	$table->index('promotion_type');
        });

        Schema::table('promotion_content_shorten_links', function (Blueprint $table) {
        	$table->index('type');
        	$table->index('original_link');
        });

    	Schema::table('promotion_schedules', function (Blueprint $table) {
        	$table->index('schedule_time');
        });

        Schema::table('promotion_sents', function (Blueprint $table) {
        	$table->index('send_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotions', function (Blueprint $table) {
        	$table->dropIndex(['promotion_name']);
        	$table->dropIndex(['promotion_type']);
        });

        Schema::table('promotion_content_shorten_links', function (Blueprint $table) {
        	$table->dropIndex(['type']);
        	$table->dropIndex(['original_link']);
        });

        Schema::table('promotion_schedules', function (Blueprint $table) {
        	$table->dropIndex(['schedule_time']);
        });

        Schema::table('promotion_sents', function (Blueprint $table) {
        	$table->dropIndex(['send_at']);
        });
    }
}