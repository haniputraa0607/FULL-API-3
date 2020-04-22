<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMoreDetailToTransactionPickupGoSendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickup_go_sends', function (Blueprint $table) {
            $table->string('driver_id')->nullable()->after('latest_status');
            $table->string('driver_photo')->nullable()->after('driver_phone');
            $table->string('vehicle_number')->nullable()->after('driver_photo');
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
            $table->dropColumn('driver_id');
            $table->dropColumn('driver_photo');
            $table->dropColumn('vehicle_number');
        });
    }
}
