<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentIdToTransactionPaymentXenditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_xendits', function (Blueprint $table) {
            $table->string('payment_id')->after('xendit_id')->nullable();
        });
        Schema::table('deals_payment_xendits', function (Blueprint $table) {
            $table->string('payment_id')->after('xendit_id')->nullable();
        });
        Schema::table('subscription_payment_xendits', function (Blueprint $table) {
            $table->string('payment_id')->after('xendit_id')->nullable();
        });
        Schema::table('transaction_academy_installment_payment_xendits', function (Blueprint $table) {
            $table->string('payment_id')->after('xendit_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payment_xendits', function (Blueprint $table) {
            $table->dropColumn('payment_id');
        });
        Schema::table('deals_payment_xendits', function (Blueprint $table) {
            $table->dropColumn('payment_id');
        });
        Schema::table('subscription_payment_xendits', function (Blueprint $table) {
            $table->dropColumn('payment_id');
        });
        Schema::table('transaction_academy_installment_payment_xendits', function (Blueprint $table) {
            $table->dropColumn('payment_id');
        });
    }
}
