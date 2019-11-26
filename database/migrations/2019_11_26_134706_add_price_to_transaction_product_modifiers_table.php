<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPriceToTransactionProductModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_product_modifiers', function (Blueprint $table) {
            $table->unsignedInteger('transaction_product_modifier_price')->after('qty');
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
            $table->dropColumn('transaction_product_modifier_price');
        });
    }
}
