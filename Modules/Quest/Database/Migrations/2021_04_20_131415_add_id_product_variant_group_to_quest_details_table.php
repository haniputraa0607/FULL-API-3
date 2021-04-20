<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdProductVariantGroupToQuestDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quest_details', function (Blueprint $table) {
            $table->unsignedInteger('id_product_variant_group')->nullable()->after('id_product');
        });
        Schema::table('quest_product_logs', function (Blueprint $table) {
            $table->unsignedInteger('id_product_variant_group')->nullable()->after('id_product');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quest_product_logs', function (Blueprint $table) {
            $table->dropColumn('id_product_variant_group');
        });
        Schema::table('quest_details', function (Blueprint $table) {
            $table->dropColumn('id_product_variant_group');
        });
    }
}
