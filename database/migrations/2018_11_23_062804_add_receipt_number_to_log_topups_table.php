<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReceiptNumberToLogTopupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_topups', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->after('id_log_topup');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_topups', function (Blueprint $table) {
            $table->dropColumn('receipt_number');
        });
    }
}
