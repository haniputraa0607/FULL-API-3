<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnPromoDeliveryToDisburseOutlet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_outlet', function (Blueprint $table) {
            $table->decimal('total_discount_delivery_charged', 30, 4)->default(0)->after('total_delivery_price');
            $table->decimal('total_discount_delivery', 30, 4)->default(0)->after('total_delivery_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disburse_outlet', function (Blueprint $table) {
            $table->dropColumn('total_discount_delivery_charged');
            $table->dropColumn('total_discount_delivery');
        });
    }
}
