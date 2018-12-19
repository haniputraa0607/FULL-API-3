<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToOutletHolidaysTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('outlet_holidays', function(Blueprint $table)
		{
			$table->foreign('id_holiday', 'fk_outlet_holidays_holidays')->references('id_holiday')->on('holidays')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_outlet', 'fk_outlet_holidays_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('outlet_holidays', function(Blueprint $table)
		{
			$table->dropForeign('fk_outlet_holidays_holidays');
			$table->dropForeign('fk_outlet_holidays_outlets');
		});
	}

}
