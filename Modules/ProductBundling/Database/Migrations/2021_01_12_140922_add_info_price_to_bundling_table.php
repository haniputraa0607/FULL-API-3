<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInfoPriceToBundlingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bundling', function (Blueprint $table) {
            $table->decimal('bundling_price_after_discount', 30,2)->default(0)->after('bundling_name');
            $table->decimal('bundling_price_before_discount', 30,2)->default(0)->after('bundling_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bundling', function (Blueprint $table) {
            $table->dropColumn('bundling_price_after_discount');
            $table->dropColumn('bundling_price_before_discount');
        });
    }
}
