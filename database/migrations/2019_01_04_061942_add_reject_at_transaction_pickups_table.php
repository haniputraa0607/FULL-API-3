<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRejectAtTransactionPickupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickups', function (Blueprint $table) {
            $table->datetime('reject_at')->nullable()->default(null)->after('taken_at');
            $table->string('reject_reason')->nullable()->after('reject_at');
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
            $table->dropColumn('reject_at');
            $table->dropColumn('reject_reason');
        });
    }
}
