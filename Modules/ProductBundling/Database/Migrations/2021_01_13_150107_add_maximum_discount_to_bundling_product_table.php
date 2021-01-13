<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaximumDiscountToBundlingProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bundling_product', function (Blueprint $table) {
            $table->decimal('bundling_product_maximum_discount', 30, 2)->default(0)->after('bundling_product_discount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bundling_product', function (Blueprint $table) {
            $table->dropColumn('bundling_product_maximum_discount');
        });
    }
}
