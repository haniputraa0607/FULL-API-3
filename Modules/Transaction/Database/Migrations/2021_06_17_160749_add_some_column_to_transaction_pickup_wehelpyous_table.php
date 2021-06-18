<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToTransactionPickupWehelpyousTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickup_wehelpyous', function (Blueprint $table) {
        	$table->string('sla')->nullable()->after('distance');
        	$table->string('order_detail_feature_type_id')->nullable()->after('order_detail_po_no');
        	$table->string('order_detail_cancel_reason_id')->nullable()->after('order_detail_status_id');
        	$table->string('order_detail_cancel_detail')->nullable()->after('order_detail_cancel_reason_id');
        	$table->string('order_detail_alfatrex_code')->nullable()->after('order_detail_gosend_code');
        	$table->string('order_detail_distance')->nullable()->after('order_detail_is_multiple');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_pickup_wehelpyous', function (Blueprint $table) {
        	$table->dropColumn('sla');
        	$table->dropColumn('order_detail_feature_type_id');
        	$table->dropColumn('order_detail_cancel_reason_id');
        	$table->dropColumn('order_detail_cancel_detail');
        	$table->dropColumn('order_detail_alfatrex_code');
        	$table->dropColumn('order_detail_distance');
        });
    }
}
