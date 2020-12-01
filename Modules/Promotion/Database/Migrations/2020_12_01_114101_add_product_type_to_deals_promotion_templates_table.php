<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProductTypeToDealsPromotionTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::table('deals_promotion_templates', function (Blueprint $table) {
        	$table->dropColumn('product_type');
        });

        Schema::table('deals_promotion_templates', function (Blueprint $table) {
        	$table->enum('product_type', ['single', 'variant'])->after('promo_type')->default('single');
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
        	$table->dropColumn('product_type');
        });

        Schema::table('deals_promotion_templates', function (Blueprint $table) {
        	$table->enum('product_type', ['single', 'group'])->after('promo_type')->default('single');
        });
    }
}
