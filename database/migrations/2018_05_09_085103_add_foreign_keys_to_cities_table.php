<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCitiesTable extends Migration {

	public function up()
	{
		Schema::table('cities', function(Blueprint $table)
		{
			$table->foreign('id_province', 'fk_cities_provinces')->references('id_province')->on('provinces')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('cities', function(Blueprint $table)
		{
			$table->dropForeign('fk_cities_provinces');
		});
	}

}
