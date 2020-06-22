<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNameAndShortAddressToTransactionPickupGoSendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickup_go_sends', function (Blueprint $table) {
            $table->string('destination_address_name')->nullable()->after('destination_address');
            $table->string('destination_short_address')->nullable()->after('destination_address');
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
            $table->dropColumn('destination_address_name');
            $table->dropColumn('destination_short_address');
        });
    }
}
