<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserContactToPaymentIpay88sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_ipay88s', function (Blueprint $table) {
            $table->string('user_contact')->nullable()->after('payment_method');
        });
        Schema::table('deals_payment_ipay88s', function (Blueprint $table) {
            $table->string('user_contact')->nullable()->after('payment_method');
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
            $table->dropColumn('user_contact');
        });
        Schema::table('deals_payment_ipay88s', function (Blueprint $table) {
            $table->dropColumn('user_contact');
        });
    }
}
