<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOutletBoxToTransactionProductServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_product_services', function (Blueprint $table) {
        	$table->unsignedInteger('id_outlet_box')->nullable()->after('flag_update_schedule');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_product_services', function (Blueprint $table) {
        	$table->dropColumn('id_outlet_box');
        });
    }
}
