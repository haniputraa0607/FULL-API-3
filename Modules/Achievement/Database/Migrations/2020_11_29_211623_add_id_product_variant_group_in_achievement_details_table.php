<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdProductVariantGroupInAchievementDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('achievement_details', function (Blueprint $table) {
            $table->unsignedBigInteger('id_product_variant_group')->after('id_product')->nullable();
            $table->foreign('id_product_variant_group', 'fk_achievement_details_id_product_variant_group')->references('id_product_variant_group')->on('product_variant_groups')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('id_product_variant_group', function (Blueprint $table) {
            $table->dropColumn('id_product_variant_group');
        });
    }
}
