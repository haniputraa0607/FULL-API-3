<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldMembershipNameColorImageUserMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users_memberships', function(Blueprint $table) {
			$table->string('membership_name')->nullable()->default(null)->after('id_membership');
			$table->char('membership_name_color',6)->nullable()->default(null)->after('membership_name');
			$table->string('membership_image')->nullable()->default(null)->after('membership_name_color');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users_memberships', function(Blueprint $table) {
            $table->dropColumn('membership_name');
            $table->dropColumn('membership_name_color');
            $table->dropColumn('membership_image');
        });
    }
}
