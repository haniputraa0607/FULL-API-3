<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPickupByTransactionPickupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickups', function (Blueprint $table) {
            $table->enum('pickup_by', ['Customer', 'GO-SEND'])->default('Customer')->after('short_link');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_pickups', function (Blueprint $table) {
            $table->dropColumn('pickup_by');
        });
    }
}
