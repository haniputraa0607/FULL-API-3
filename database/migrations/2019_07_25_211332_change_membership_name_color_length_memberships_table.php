<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeMembershipNameColorLengthMembershipsTable extends Migration
{
    public function __construct()
    {
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
            $table->string('membership_name_color', 50)->change();
        });
        Schema::table('users_memberships', function (Blueprint $table) {
            $table->string('membership_name_color', 50)->change();
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
            $table->char('membership_name_color', 6)->change();
        });
        Schema::table('users_memberships', function (Blueprint $table) {
            $table->char('membership_name_color', 6)->change();
        });
    }
}
