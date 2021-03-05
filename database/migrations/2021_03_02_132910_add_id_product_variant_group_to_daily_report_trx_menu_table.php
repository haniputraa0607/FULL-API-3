<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdProductVariantGroupToDailyReportTrxMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_report_trx_menu', function (Blueprint $table) {
            $table->unsignedInteger('id_product_variant_group')->index('daily_report_trx_menu_id_product_variant_group_index')->after('id_product')->nullable();
            $table->unsignedInteger('id_product_category')->index('daily_report_trx_menu_id_product_category_index')->after('id_product_variant_group')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('daily_report_trx_menu', function (Blueprint $table) {
            $table->dropIndex('daily_report_trx_menu_id_product_variant_group_index');
            $table->dropIndex('daily_report_trx_menu_id_product_category_index');
            $table->dropColumn('id_product_variant_group');
            $table->dropColumn('id_product_category');
        });
    }
}
