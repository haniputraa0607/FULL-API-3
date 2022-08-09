<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTaxAndServiceToTransactionGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_groups', function (Blueprint $table) {
            $table->integer('transaction_service')->nullable()->default(0)->after('transaction_shipment');
            $table->integer('transaction_tax')->nullable()->default(0)->after('transaction_shipment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_groups', function (Blueprint $table) {
            $table->dropColumn('transaction_service');
            $table->dropColumn('transaction_tax');
        });
    }
}
