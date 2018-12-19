<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMembershipsTable extends Migration {

	public function up()
	{
		Schema::create('memberships', function(Blueprint $table)
		{
			$table->increments('id_membership');
			$table->string('membership_name');
			$table->integer('min_total_value')->nullable();
			$table->integer('min_total_count')->nullable();
			$table->integer('retain_days')->nullable();
			$table->integer('retain_min_total_value')->nullable();
			$table->integer('retain_min_total_count')->nullable();
			$table->decimal('benefit_point_multiplier', 10)->nullable();
			$table->decimal('benefit_cashback_multiplier', 10)->nullable();
			$table->string('benefit_promo_id', 50)->nullable();
			$table->decimal('benefit_discount', 10)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('memberships');
	}

}
