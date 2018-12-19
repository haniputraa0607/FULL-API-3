<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLogPointsTable extends Migration {

	public function up()
	{
		Schema::create('log_points', function(Blueprint $table)
		{
			$table->integer('id_log_point', true);
			$table->integer('id_user')->unsigned()->index('fk_log_points_users');
			$table->integer('point')->default(0);
			$table->integer('id_reference')->nullable();
			$table->string('source', 191)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('log_points');
	}

}
