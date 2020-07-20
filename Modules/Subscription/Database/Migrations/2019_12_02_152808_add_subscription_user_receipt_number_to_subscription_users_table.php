<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubscriptionUserReceiptNumberToSubscriptionUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_users', function (Blueprint $table) {
            $table->string('subscription_user_receipt_number')->nullable()->after('id_subscription')->index('index_subscription_user_receipt_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_users', function (Blueprint $table) {
            $table->dropColumn('subscription_user_receipt_number');
        });
    }
}
