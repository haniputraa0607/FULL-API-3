<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsShipmentMethodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_shipment_methods', function (Blueprint $table) {
            $table->increments('id_deals_shipment_method');
            $table->unsignedInteger('id_deals');
            $table->string('shipment_method');
            $table->timestamps();

            $table->foreign('id_deals', 'fk_deals_shipment_methods_deals')->references('id_deals')->on('deals')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_shipment_methods');
    }
}
