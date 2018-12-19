<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddComeFieldToTransactionPickupsTable extends Migration
{
    public function up()
    {
        Schema::table('transaction_pickups', function (Blueprint $table) {
            $table->dateTime('pickup_at')->nullable()->after('short_link');
        });
    }

    public function down()
    {
        Schema::table('transaction_pickups', function (Blueprint $table) {
            $table->dropColumn('pickup_at');
        });
    }
}
