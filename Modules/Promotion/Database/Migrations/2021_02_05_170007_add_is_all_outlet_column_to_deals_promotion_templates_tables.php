<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsAllOutletColumnToDealsPromotionTemplatesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_promotion_templates', function (Blueprint $table) {
        	$table->tinyInteger('is_all_outlet')->default(0)->nullable()->after('deals_list_outlet');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_promotion_templates', function (Blueprint $table) {
        	$table->tinyInteger('is_all_outlet')->default(0)->nullable()->after('deals_list_outlet');
        });
    }
}
