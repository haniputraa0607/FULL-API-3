<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPaymentSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_payment_subscriptions', function (Blueprint $table) {
            $table->increments('id_transaction_payment_subscription');
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_subscription_user_voucher');
            $table->integer('subscription_nominal');
            $table->timestamps();

            $table->index(["id_transaction"], 'fk_id_transaction_transaction_payment_subscription_idx');
            $table->foreign('id_transaction', 'fk_id_transaction_transaction_payment_subscription_idx')
                ->references('id_transaction')->on('transactions')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index(["id_subscription_user_voucher"], 'fk_id_subs_user_voucher_transaction_payment_subscription_idx');
            $table->foreign('id_subscription_user_voucher', 'fk_id_subs_user_voucher_transaction_payment_subscription_idx')
                ->references('id_subscription_user_voucher')->on('subscription_user_vouchers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_payment_subscriptions');
    }
}
