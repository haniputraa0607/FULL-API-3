<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndomaretToMidtransTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_midtrans', function (Blueprint $table) {
            $table->string('store')->after('eci')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payment_midtrans', function (Blueprint $table) {
            $table->dropColumn('store');
        });
    }
}
