<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSectionVisibilityToDashboardUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dashboard_users', function (Blueprint $table) {
            $table->boolean('section_visible')->default(1)->after('section_title');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dashboard_users', function (Blueprint $table) {
            $table->dropColumn('section_visible');
        });
    }
}
