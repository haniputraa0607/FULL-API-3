<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToEnquiriesTable extends Migration {

	public function up()
	{
		Schema::table('enquiries', function(Blueprint $table)
		{
			$table->foreign('id_outlet', 'fk_enquiries_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('enquiries', function(Blueprint $table)
		{
			$table->dropForeign('fk_enquiries_outlets');
		});
	}

}
