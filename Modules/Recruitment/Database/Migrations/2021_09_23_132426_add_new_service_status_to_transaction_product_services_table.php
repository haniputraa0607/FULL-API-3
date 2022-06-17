<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewServiceStatusToTransactionProductServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_product_services', function (Blueprint $table) {
        	DB::statement("ALTER TABLE transaction_product_services CHANGE COLUMN service_status service_status ENUM('In Progress','Completed','Stopped') NULL");
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
        	DB::statement("ALTER TABLE transaction_product_services CHANGE COLUMN service_status service_status ENUM('In Progress','Completed') NULL");
        });
    }
}
