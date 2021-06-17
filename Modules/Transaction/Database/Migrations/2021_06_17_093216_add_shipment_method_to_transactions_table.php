<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShipmentMethodToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
        	$table->enum('shipment_method', ['GO-SEND', 'Wehelpyou'])->nullable()->after('trasaction_type');
        	$table->string('shipment_courier')->nullable()->after('shipment_method');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
        	$table->dropColumn('shipment_method');
        	$table->dropColumn('shipment_courier');
        });
    }
}
