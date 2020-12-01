<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdProductVariantGroupToAllSubscriptionProductRequirementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_products', function (Blueprint $table) {
        	$table->unsignedBigInteger('id_product_variant_group')->after('id_product_category')->nullable()->index('fk_subscription_product_product_variant_group');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_products', function (Blueprint $table) {
        	$table->dropColumn('id_product_variant_group');
        });
    }
}
