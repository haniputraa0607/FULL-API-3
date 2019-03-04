<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMinBalanceMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->enum('membership_type', ['count', 'value', 'balance'])->default('value')->after('membership_image');
            $table->integer('min_total_balance')->nullable()->after('min_total_count');
            $table->integer('retain_min_total_balance')->nullbale()->after('retain_min_total_count');
        });
        Schema::table('users_memberships', function (Blueprint $table) {
            $table->enum('membership_type', ['count', 'value', 'balance'])->default('value')->after('membership_image');
            $table->integer('min_total_balance')->nullable()->after('min_total_count');
            $table->integer('retain_min_total_balance')->nullbale()->after('retain_min_total_count');
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
            $table->dropColumn('membership_type');
            $table->dropColumn('min_total_balance');
            $table->dropColumn('retain_min_total_balance');
        });
         Schema::table('users_memberships', function (Blueprint $table) {
            $table->dropColumn('membership_type');
            $table->dropColumn('min_total_balance');
            $table->dropColumn('retain_min_total_balance');
        });
    }
}
