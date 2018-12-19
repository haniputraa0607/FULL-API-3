<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateNewsOutletsTable extends Migration {

	public function up()
	{
		Schema::create('news_outlets', function(Blueprint $table)
		{
			$table->integer('id_outlet')->unsigned()->index('fk_news_outlets_outlets');
			$table->integer('id_news')->unsigned()->index('fk_news_outlets_news');
			$table->primary(['id_outlet','id_news']);
		});
	}

	public function down()
	{
		Schema::drop('news_outlets');
	}

}
