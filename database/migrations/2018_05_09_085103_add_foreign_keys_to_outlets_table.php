<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToOutletsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('outlets', function(Blueprint $table)
		{
			$table->foreign('id_city', 'fk_outlets_cities')->references('id_city')->on('cities')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('outlets', function(Blueprint $table)
		{
			$table->dropForeign('fk_outlets_cities');
		});
	}

}
