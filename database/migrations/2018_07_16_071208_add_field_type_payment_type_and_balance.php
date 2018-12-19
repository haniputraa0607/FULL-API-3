<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldTypePaymentTypeAndBalance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_users', function (Blueprint $table) {
            $table->enum('payment_method', ['Manual', 'Midtrans', 'Offline', 'Balance'])->nullable()->after('voucher_price_cash');
            $table->integer('balance_nominal')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_users', function (Blueprint $table) {
            $table->dropColumn('payment_method');
            $table->dropColumn('balance_nominal');
        });
    }
}
