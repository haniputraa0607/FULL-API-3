<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeShipmentCourierType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE transaction_shipments CHANGE COLUMN shipment_courier shipment_courier VARCHAR(191) NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `transaction_shipments` CHANGE `shipment_courier` `shipment_courier` ENUM("jne","pos","tiki","pcp","esl","rpx","pandu","wahana","sicepat","j&t","pahala","cahaya","sat","jet","indah","slis","dse","first","ncs","star") NULL;');
    }
}
