<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToOutletPhotosTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('outlet_photos', function(Blueprint $table)
		{
			$table->foreign('id_outlet', 'fk_outlet_photos_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('outlet_photos', function(Blueprint $table)
		{
			$table->dropForeign('fk_outlet_photos_outlets');
		});
	}

}
