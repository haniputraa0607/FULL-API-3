<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionUserVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_user_vouchers', function (Blueprint $table) {
            $table->increments('id_subscription_user_voucher');
            $table->integer('id_subscription_user')->unsigned();
            $table->string('voucher_code', 20);
            $table->dateTime('used_at')->nullable();
            $table->integer('id_transaction')->unsigned();
            $table->timestamps();
            
            $table->foreign('id_subscription_user', 'fk_subscription_users_subscriptions_user_vouchers')->references('id_subscription_user')->on('subscription_users')->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->foreign('id_transaction', 'fk_transactions_subscriptions_user_vouchers')->references('id_transaction')->on('transactions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_user_vouchers');
    }
}
