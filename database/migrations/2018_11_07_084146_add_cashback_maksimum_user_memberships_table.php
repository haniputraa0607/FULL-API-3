<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCashbackMaksimumUserMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users_memberships', function (Blueprint $table) {
			$table->integer('cashback_maximum')->nullable()->after('benefit_discount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users_memberships', function (Blueprint $table) {
            $table->dropColumn('cashback_maximum');
        });
    }
}
