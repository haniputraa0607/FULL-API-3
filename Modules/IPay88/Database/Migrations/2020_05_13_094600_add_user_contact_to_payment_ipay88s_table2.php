<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserContactToPaymentIpay88sTable2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_payment_ipay88s', function (Blueprint $table) {
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
        Schema::table('subscription_payment_ipay88s', function (Blueprint $table) {
            $table->dropColumn('user_contact');
        });
    }
}
