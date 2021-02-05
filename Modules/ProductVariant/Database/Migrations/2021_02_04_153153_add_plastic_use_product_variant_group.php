<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPlasticUseProductVariantGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_variant_groups', function (Blueprint $table) {
            $table->integer('product_variant_groups_plastic_used')->nullable()->after('product_variant_group_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_variant_groups', function (Blueprint $table) {
            $table->dropColumn('product_variant_groups_plastic_used');
        });
    }
}
