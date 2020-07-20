<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVoidDateToSubscriptionUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_users', function (Blueprint $table) {
            $table->dateTime('void_date')->nullable()->after('paid_status');
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
            $table->dropColumn('void_date');
        });
    }
}
