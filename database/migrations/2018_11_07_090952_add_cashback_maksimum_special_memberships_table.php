<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCashbackMaksimumSpecialMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('special_memberships', function (Blueprint $table) {
			$table->integer('cashback_maximum')->nullable()->after('benefit_cashback_multiplier');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('special_memberships', function (Blueprint $table) {
            $table->dropColumn('cashback_maximum');
        });
    }
}
