<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDoctorInboxesTable extends Migration {

	public function up()
	{
		Schema::create('doctor_inboxes', function(Blueprint $table)
		{
			$table->increments('id_doctor_inboxes');
			$table->integer('id_campaign')->unsigned()->nullable()->index('fk_doctor_inboxes_campaigns');
			$table->integer('id_doctor')->unsigned()->index('fk_doctor_inboxes_users');
			$table->string('inboxes_subject');
			$table->text('inboxes_content', 16777215)->nullable();
			$table->dateTime('inboxes_send_at')->nullable();
			$table->timestamps();
		});
	}

	public function down() 
	{
		Schema::drop('doctor_inboxes');
	}

}
