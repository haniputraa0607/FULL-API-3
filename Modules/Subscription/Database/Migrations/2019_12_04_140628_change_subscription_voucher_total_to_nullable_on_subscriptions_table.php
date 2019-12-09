<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeSubscriptionVoucherTotalToNullableOnSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('subscription_voucher_total')->nullable(true)->change();
            $table->text('subscription_description')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('subscription_voucher_total')->nullable(false)->change();
            $table->text('subscription_description')->nullable(false)->change();
        });
    }
}
