<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFaqDoctorsTable extends Migration {

	public function up()
	{
		Schema::create('faq_doctors', function(Blueprint $table)
		{
			$table->increments('id_faq_doctor');
			$table->text('question', 65535);
			$table->text('answer', 65535);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('faq_doctors');
	}

}
