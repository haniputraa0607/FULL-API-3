<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubscriptionActiveAtColumnToSubscriptionUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_users', function (Blueprint $table) {
            $table->dateTime('subscription_active_at')->nullable()->after('bought_at');
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
            $table->dropColumn('subscription_active_at');
        });
    }
}
