<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPhotoToTransactionPickupWehelpyousTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickup_wehelpyous', function (Blueprint $table) {
        	$table->text('tracking_photo')->after('tracking_vehicle_number')->nullable();
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
        	$table->dropColumn('tracking_photo');
        });
    }
}
