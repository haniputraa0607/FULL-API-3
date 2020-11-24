<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdBrandColumnToSomeDealsProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_buyxgety_product_requirements', function (Blueprint $table) {
        	$table->integer('id_brand')->nullable()->after('id_product')->index();
        });

        Schema::table('deals_buyxgety_rules', function (Blueprint $table) {
        	$table->integer('id_brand')->nullable()->after('benefit_id_product')->index();
        });

        Schema::table('deals_product_discounts', function (Blueprint $table) {
        	$table->integer('id_brand')->nullable()->after('id_product')->index();
        });

        Schema::table('deals_tier_discount_products', function (Blueprint $table) {
        	$table->integer('id_brand')->nullable()->after('id_product')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_buyxgety_product_requirements', function (Blueprint $table) {
        	$table->dropColumn('id_brand');
        });

        Schema::table('deals_buyxgety_rules', function (Blueprint $table) {
        	$table->dropColumn('id_brand');
        });

        Schema::table('deals_product_discounts', function (Blueprint $table) {
        	$table->dropColumn('id_brand');
        });

        Schema::table('deals_tier_discount_products', function (Blueprint $table) {
        	$table->dropColumn('id_brand');
        });
    }
}
