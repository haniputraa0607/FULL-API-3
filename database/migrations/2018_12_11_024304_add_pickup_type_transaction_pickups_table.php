<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPickupTypeTransactionPickupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickups', function (Blueprint $table) {
            $table->enum('pickup_type', ['set time', 'right now', 'at arrival'])->default('set time')->after('short_link');
            $table->timestamp('ready_at')->nullable()->after('receive_at');
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
            $table->dropColumn('pickup_type');
            $table->dropColumn('ready_at');
        });
    }
}
