<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToUserAddressesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_addresses', function(Blueprint $table)
		{
			$table->foreign('id_city', 'fk_user_addresses_cities')->references('id_city')->on('cities')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_user', 'fk_user_addresses_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('user_addresses', function(Blueprint $table)
		{
			$table->dropForeign('fk_user_addresses_cities');
			$table->dropForeign('fk_user_addresses_users');
		});
	}

}
