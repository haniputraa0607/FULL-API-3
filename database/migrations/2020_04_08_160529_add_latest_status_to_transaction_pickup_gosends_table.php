<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLatestStatusToTransactionPickupGosendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickup_go_sends', function (Blueprint $table) {
            $table->string('latest_status')->nullable()->after('go_send_order_no');
            $table->string('driver_name')->nullable()->after('latest_status');
            $table->string('driver_phone')->nullable()->after('driver_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_pickup_go_sends', function (Blueprint $table) {
            $table->dropColumn('latest_status');
            $table->dropColumn('driver_name');
            $table->dropColumn('driver_phone');
        });
    }
}
