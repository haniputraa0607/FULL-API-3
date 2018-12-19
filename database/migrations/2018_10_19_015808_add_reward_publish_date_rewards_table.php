<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRewardPublishDateRewardsTable extends Migration
{
    public function up()
    {
        Schema::table('rewards', function (Blueprint $table) {
			$table->date('reward_publish_start')->nullable()->after('reward_end');
			$table->date('reward_publish_end')->nullable()->after('reward_publish_start');
			$table->integer('count_winner')->nullable()->after('reward_publish_end');
			$table->enum('winner_type', ['Choosen', 'Highest Coupon', 'Random'])->nullable()->after('count_winner');
        });
    }

    public function down()
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropColumn('reward_publish_start');
            $table->dropColumn('reward_publish_end');
            $table->dropColumn('count_winner');
            $table->dropColumn('winner_type');
        });
    }
}
