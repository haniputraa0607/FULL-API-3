<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQtyToFavoriteModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('favorite_modifiers', function (Blueprint $table) {
            $table->unsignedInteger('qty')->after('id_product_modifier');
            $table->foreign('id_product_modifier','fk_favorite_modifiers_product_modifier')->references('id_product_modifier')->on('product_modifiers')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('favorite_modifiers', function (Blueprint $table) {
            $table->dropForeign('fk_favorite_modifiers_product_modifier');
            $table->dropColumn('qty');
        });
    }
}
