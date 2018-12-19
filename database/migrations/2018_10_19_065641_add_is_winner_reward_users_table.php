<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsWinnerRewardUsersTable extends Migration
{
    public function up()
    {
        Schema::table('reward_users', function (Blueprint $table) {
			$table->char('is_winner', '1')->nullable()->after('total_coupon');
        });
    }

    public function down()
    {
        Schema::table('reward_users', function (Blueprint $table) {
            $table->dropColumn('is_winner');
        });
    }
}
