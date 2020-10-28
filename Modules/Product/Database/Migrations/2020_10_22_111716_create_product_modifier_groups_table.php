<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductModifierGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_modifier_groups', function (Blueprint $table) {
            $table->increments('id_product_modifier_group');
            $table->string('product_modifier_group_name');
            $table->timestamps();
        });

        Schema::table('product_modifiers', function (Blueprint $table) {
            $table->unsignedInteger('id_product_modifier_group')->nullable()->after('id_product_modifier');
            $table->foreign('id_product_modifier_group', 'fk_ipmg_pm_pmg')->on('product_modifier_groups')->references('id_product_modifier_group')->onDelete('set null');
        });

        Schema::table('transaction_product_modifiers', function (Blueprint $table) {
            $table->unsignedInteger('id_product_modifier_group')->nullable()->after('id_product_modifier');
            $table->foreign('id_product_modifier_group', 'fk_ipmg_tpm_pmg')->on('product_modifier_groups')->references('id_product_modifier_group')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_product_modifiers', function (Blueprint $table) {
            $table->dropForeign('fk_ipmg_tpm_pmg');
            $table->dropColumn('id_product_modifier_group');
        });
        Schema::table('product_modifiers', function (Blueprint $table) {
            $table->dropForeign('fk_ipmg_pm_pmg');
            $table->dropColumn('id_product_modifier_group');
        });
        Schema::dropIfExists('product_modifier_groups');
    }
}
