<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToNewsOutletsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('news_outlets', function(Blueprint $table)
		{
			$table->foreign('id_news', 'fk_news_outlets_news')->references('id_news')->on('news')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('news_outlets', function(Blueprint $table)
		{
			$table->dropForeign('fk_news_outlets_news');
		});
	}

}
