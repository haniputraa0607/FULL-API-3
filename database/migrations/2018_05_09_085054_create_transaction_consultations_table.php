<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTransactionConsultationsTable extends Migration {

	public function up()
	{
		Schema::create('transaction_consultations', function(Blueprint $table)
		{
			$table->increments('id_transaction_consultation');
			$table->integer('id_transaction')->unsigned()->index('fk_transaction_consultations_transactions');
			$table->string('consultation_type');
			$table->integer('id_doctor')->unsigned()->index('fk_transaction_consultations_doctors');
			$table->integer('id_user')->unsigned()->index('fk_transaction_consultations_user');
			$table->date('schedule_date');
			$table->time('schedule_start_time');
			$table->time('schedule_end_time');
			$table->string('consultation_session_price');
			$table->dateTime('consultation_start_at');
			$table->dateTime('consultation_end_at');
			$table->enum('consultation_status', array('soon', 'ongoing', 'done'))->default('soon');
			
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('transaction_consultations');
	}

}
