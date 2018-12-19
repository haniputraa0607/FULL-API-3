<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable extends Migration {

	public function up()
	{
		Schema::create('users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name')->nullable();
			$table->string('phone', 15)->unique();
			$table->integer('id_membership')->unsigned()->index('fk_users_memberships')->nullable()->default(null);
			$table->string('email', 191)->nullable()->unique();
			$table->string('password');
			$table->integer('id_city')->unsigned()->nullable()->index('fk_users_cities');
			$table->enum('gender', array('Male','Female'))->nullable();
			$table->enum('provider', array('Telkomsel','XL','Indosat','Tri','Axis','Smart'))->nullable();
			$table->date('birthday')->nullable();
			$table->char('phone_verified', 1)->default(0);
			$table->char('email_verified', 1)->default(0);
			$table->enum('level', array('Super Admin','Admin','Admin Outlet','Customer'))->default('Customer');
			$table->integer('points')->default(0);
			$table->string('android_device', 200)->nullable();
			$table->string('ios_device', 200)->nullable();
			$table->char('is_suspended', 1)->default(0);
			$table->string('remember_token', 100)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('users');
	}

}
