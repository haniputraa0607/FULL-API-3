<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCurrentMembershipSettingsLogPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_points', function(Blueprint $table)
		{
			$table->integer('voucher_price')->default(0)->after('source');
			$table->integer('grand_total')->default(0)->after('voucher_price');
			$table->integer('point_conversion')->default(0)->after('grand_total');
			$table->string('membership_level')->nullable()->default(null)->after('point_conversion');
			$table->integer('membership_point_percentage')->default(0)->after('membership_level');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_points', function(Blueprint $table) {
            $table->dropColumn('voucher_price');
            $table->dropColumn('grand_total');
            $table->dropColumn('point_conversion');
            $table->dropColumn('membership_level');
            $table->dropColumn('membership_percentage');
        });
    }
}
