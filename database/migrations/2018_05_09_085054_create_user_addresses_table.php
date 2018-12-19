<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserAddressesTable extends Migration {

	public function up()
	{
		Schema::create('user_addresses', function(Blueprint $table)
		{
			$table->increments('id_user_address');
			$table->string('name', 191);
			$table->string('phone', 191);
			$table->integer('id_user')->unsigned()->nullable()->index('fk_user_addresses_users');
			$table->integer('id_city')->unsigned()->index('fk_user_addresses_cities');
			$table->text('address', 65535)->nullable();
			$table->string('postal_code')->nullable();
			$table->text('description', 65535)->nullable();
			$table->enum('primary', array('0','1'))->default('0');
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('user_addresses');
	}

}
