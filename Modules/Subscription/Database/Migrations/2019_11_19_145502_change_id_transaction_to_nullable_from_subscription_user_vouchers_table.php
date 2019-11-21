<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdTransactionToNullableFromSubscriptionUserVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_user_vouchers', function (Blueprint $table) {
            $table->integer('id_transaction')->unsigned()->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_user_vouchers', function (Blueprint $table) {
            $table->integer('id_transaction')->unsigned()->nullable(true)->change();
        });
    }
}
