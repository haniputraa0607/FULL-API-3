<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersMembershipsTable extends Migration {

	public function up()
	{
		Schema::create('users_memberships', function(Blueprint $table)
		{
			$table->integer('id_log_membership')->unsigned()->primary();
			$table->integer('id_user')->unsigned()->index('id_user');
			$table->integer('id_membership')->unsigned()->index('fk_users_memberships_memberships');
			$table->integer('min_total_value')->unsigned()->nullable();
			$table->integer('min_total_count')->unsigned()->nullable();
			$table->dateTime('retain_date')->nullable()->default(null);
			$table->integer('retain_min_total_value')->unsigned()->nullable();
			$table->integer('retain_min_total_count')->unsigned()->nullable();
			$table->integer('benefit_point_multiplier')->unsigned()->nullable();
			$table->integer('benefit_cashback_multiplier')->unsigned()->nullable();
			$table->integer('benefit_promo_id')->unsigned()->nullable();
			$table->integer('benefit_discount')->unsigned()->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('users_memberships');
	}

}
