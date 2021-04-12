<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddManualRefundToTransactionPaymentShopeePaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_shopee_pays', function (Blueprint $table) {
            $table->boolean('manual_refund')->after('id_transaction')->default(0);
        });
        Schema::table('deals_payment_shopee_pays', function (Blueprint $table) {
            $table->boolean('manual_refund')->after('id_deals_user')->default(0);
        });
        Schema::table('subscription_payment_shopee_pays', function (Blueprint $table) {
            $table->boolean('manual_refund')->after('id_subscription_user')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_payment_shopee_pays', function (Blueprint $table) {
            $table->dropColumn('manual_refund');
        });
        Schema::table('deals_payment_shopee_pays', function (Blueprint $table) {
            $table->dropColumn('manual_refund');
        });
        Schema::table('transaction_payment_shopee_pays', function (Blueprint $table) {
            $table->dropColumn('manual_refund');
        });
    }
}
