<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRetryCountAndStopBookingAtToTransactionPickupWehelpyousTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickup_wehelpyous', function (Blueprint $table) {
        	$table->unsignedInteger('retry_count')->default(0)->after('latest_status_id');
        	$table->dateTime('stop_booking_at')->nullable()->after('retry_count');
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
        	$table->dropColumn('retry_count');
        	$table->dropColumn('stop_booking_at');
        });
    }
}
