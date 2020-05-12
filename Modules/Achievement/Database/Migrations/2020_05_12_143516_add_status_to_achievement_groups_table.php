<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusToAchievementGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('achievement_groups', function (Blueprint $table) {
            $table->tinyInteger('is_calculate')->after('order_by')->default(1);
            $table->string('status', 10)->after('order_by')->default('Active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('achievement_groups', function (Blueprint $table) {
        });
    }
}
