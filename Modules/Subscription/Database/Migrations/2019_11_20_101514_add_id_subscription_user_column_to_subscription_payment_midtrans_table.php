<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdSubscriptionUserColumnToSubscriptionPaymentMidtransTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_payment_midtrans', function (Blueprint $table) {
            $table->unsignedInteger('id_subscription_user')->after('id_subscription');
            $table->foreign('id_subscription_user', 'fk_subscription_users_subscription_payment_midtrans')->references('id_subscription_user')->on('subscription_users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_payment_midtrans', function (Blueprint $table) {
            $table->dropForeign('fk_subscription_users_subscription_payment_midtrans');
            $table->dropColumn('id_subscription_user');
        });
    }
}
