<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAchievementToMembershipsTable extends Migration
{
    public function __construct()
    {
        // Register ENUM type
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn('membership_type');
        });
        Schema::table('memberships', function (Blueprint $table) {
            $table->enum('membership_type', ['count', 'value', 'balance', 'achievement'])->after('membership_next_image');
            $table->integer('min_total_achievement')->nullable()->after('min_total_balance');
            $table->integer('retain_min_total_achievement')->nullable()->after('retain_min_total_balance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('memberships', function (Blueprint $table) {
        });
    }
}
