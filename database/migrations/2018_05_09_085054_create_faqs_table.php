<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFaqsTable extends Migration {

	public function up()
	{
		Schema::create('faqs', function(Blueprint $table)
		{
			$table->increments('id_faq');
			$table->text('question', 65535);
			$table->text('answer', 65535);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('faqs');
	}

}
