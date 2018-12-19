<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNamePhoneEmailOutletUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_outlets', function(Blueprint $table)
		{
			$table->increments('id_user_outlet')->after('id_user');
			$table->string('phone',25)->after('id_user_outlet');
			$table->string('email')->after('phone');
			$table->string('name')->after('email');
			$table->char('payment',1)->after('delivery');
		});
		Schema::table('user_outlets', function(Blueprint $table) {
            $table->dropColumn('id_user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_outlets', function(Blueprint $table) {
            $table->dropColumn('phone');
            $table->dropColumn('email');
            $table->dropColumn('name');
        });
    }
}
