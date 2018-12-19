<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEnquiriesTable extends Migration {

	public function up()
	{
		Schema::create('enquiries', function(Blueprint $table)
		{
			$table->increments('id_enquiry');
			$table->integer('id_outlet')->unsigned()->default(0)->index('fk_enquiries_outlets');
			$table->string('enquiry_name', 200)->nullable();
			$table->string('enquiry_phone', 18)->nullable();
			$table->string('enquiry_email', 200)->nullable();
			$table->enum('enquiry_subject', array('Question','Complaint','Partnership'))->default('Question');
			$table->text('enquiry_content', 16777215)->nullable();
			$table->string('enquiry_photo', 200)->nullable();
			$table->enum('enquiry_status', array('Read','Unread'))->default('Unread');
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('enquiries');
	}

}
