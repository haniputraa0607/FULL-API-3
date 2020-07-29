<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMembershipCardToMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('membership_card')->nullable()->after('membership_image');
        });
        Schema::table('users_memberships', function (Blueprint $table) {
            $table->string('membership_card')->nullable()->after('membership_image');
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
            $table->dropColumn('membership_card');
        });
        Schema::table('users_memberships', function (Blueprint $table) {
            $table->dropColumn('membership_card');
        });
    }
}
