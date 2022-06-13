<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdSubdistrictToTransactionShipment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_shipments', function (Blueprint $table) {
            $table->integer('depart_id_subdistrict')->nullable()->after('depart_id_city');
            $table->integer('destination_id_subdistrict')->nullable()->after('destination_id_city');
            $table->integer('shipment_insurance_price')->nullable()->after('shipment_total_weight');
            $table->smallInteger('shipment_insurance_use_status')->nullable()->after('shipment_total_weight');
            $table->integer('shipment_rate_id')->nullable()->after('shipment_total_weight');
            $table->integer('shipment_price')->nullable()->after('shipment_total_weight');
            $table->integer('shipment_total_height')->nullable()->after('destination_description');
            $table->integer('shipment_total_width')->nullable()->after('destination_description');
            $table->integer('shipment_total_length')->nullable()->after('destination_description');
            $table->string('shipment_pickup_code')->nullable()->after('shipment_courier_etd');
            $table->dateTime('shipment_pickup_time_end')->nullable()->after('shipment_courier_etd');
            $table->dateTime('shipment_pickup_time_start')->nullable()->after('shipment_courier_etd');
            $table->timestamps();
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
            $table->dropColumn('depart_id_subdistrict');
            $table->dropColumn('destination_id_subdistrict');
            $table->dropColumn('shipment_rate_id');
            $table->dropColumn('shipment_insurance_price');
            $table->dropColumn('shipment_insurance_use_status');
            $table->dropColumn('shipment_price');
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
            $table->dropColumn('shipment_total_height');
            $table->dropColumn('shipment_total_width');
            $table->dropColumn('shipment_total_length');
            $table->dropColumn('shipment_pickup_time_start');
            $table->dropColumn('shipment_pickup_time_end');
            $table->dropColumn('shipment_pickup_code');
        });
    }
}
