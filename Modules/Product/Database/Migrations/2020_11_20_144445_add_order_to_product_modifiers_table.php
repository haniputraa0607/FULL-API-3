<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderToProductModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_modifiers', function (Blueprint $table) {
            $table->bigInteger('product_modifier_order')->nullable()->after('product_modifier_visibility');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_modifiers', function (Blueprint $table) {
            $table->dropColumn('product_modifier_order');
        });
    }
}
