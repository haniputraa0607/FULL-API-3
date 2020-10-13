<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsAllShipmentAndIsAllPaymentMethodColumnToDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals', function (Blueprint $table) {
        	$table->boolean('is_all_shipment')->nullable()->after('is_all_outlet');
        	$table->boolean('is_all_payment')->nullable()->after('is_all_shipment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals', function (Blueprint $table) {
        	$table->dropColumn('is_all_shipment');
        	$table->dropColumn('is_all_payment');
        });
    }
}
