<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdSubscriptionUserVoucherOnTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
        	$table->unsignedInteger('id_subscription_user_voucher')->after('id_promo_campaign_promo_code')->nullable();

        	// $table->index(["id_subscription_user_voucher"], 'fk_id_subscription_user_vouchers_transactions');
            $table->foreign('id_subscription_user_voucher', 'fk_id_subscription_user_vouchers_transactions')
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
        Schema::table('transactions', function (Blueprint $table) {
        	$table->dropForeign('fk_id_subscription_user_vouchers_transactions');

        	$table->dropColumn('id_subscription_user_voucher');
        });
    }
}
