<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRewardCouponPointLogPointsTable extends Migration
{
    public function up()
    {
        Schema::table('log_points', function (Blueprint $table) {
			$table->integer('reward_coupon_point')->nullable()->after('membership_point_percentage');
			$table->integer('reward_total_coupon')->nullable()->after('reward_coupon_point');
        });
    }

    public function down()
    {
        Schema::table('log_points', function (Blueprint $table) {
            $table->dropColumn('reward_coupon_point');
            $table->dropColumn('reward_total_coupon');
        });
    }
}
