<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAmountToIntegerIpay88 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_ipay88s', function (Blueprint $table) {
            $table->unsignedInteger('amount')->nullable()->change();
        });
        Schema::table('deals_payment_ipay88s', function (Blueprint $table) {
            $table->unsignedInteger('amount')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payment_ipay88s', function (Blueprint $table) {
            $table->decimal('amount',15,2)->default(0)->change();
        });
        Schema::table('deals_payment_ipay88s', function (Blueprint $table) {
            $table->decimal('amount',15,2)->default(0)->change();
        });
    }
}
