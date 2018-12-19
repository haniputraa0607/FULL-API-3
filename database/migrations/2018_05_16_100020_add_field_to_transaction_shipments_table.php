<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldToTransactionShipmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_shipments', function(Blueprint $table)
		{
			$table->string('short_link')->after('shipment_courier_etd');
            $table->timestamp('receive_at')->nullable()->after('short_link');
            $table->timestamp('taken_at')->nullable()->after('receive_at');;
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_shipments', function (Blueprint $table) {
            $table->dropColumn('short_link');
            $table->dropColumn('receive_at');
            $table->dropColumn('taken_at');
        });
    }
}
