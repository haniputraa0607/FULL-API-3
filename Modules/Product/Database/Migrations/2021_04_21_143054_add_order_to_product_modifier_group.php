<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderToProductModifierGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_modifier_groups', function (Blueprint $table) {
            $table->smallInteger('product_modifier_group_order')->after('product_modifier_group_name')->nullable()->default(999);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_modifier_groups', function (Blueprint $table) {
            $table->dropColumn('product_modifier_group_order');
        });
    }
}
