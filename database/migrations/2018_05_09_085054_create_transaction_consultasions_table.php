<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTransactionConsultasionsTable extends Migration {

	public function up()
	{
		Schema::create('transaction_consultasions', function(Blueprint $table)
		{
			$table->increments('id_transaction_consultasion');
			$table->integer('id_transaction')->unsigned()->index('fk_transaction_consultasions_transactions');
			$table->integer('id_doctor')->unsigned()->index('fk_transaction_consultasions_doctors');
			$table->integer('id_user')->unsigned()->index('fk_transaction_consultasions_user');
			$table->date('schedule_date');
			$table->time('schedule_start_time');
			$table->time('schedule_end_time');
			$table->dateTime('consultasion_start_at');
			$table->dateTime('consultasion_end_at');
			$table->enum('consultasion_status', array('soon', 'ongoing', 'done'))->default('soon');
			
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('transaction_consultasions');
	}

}
