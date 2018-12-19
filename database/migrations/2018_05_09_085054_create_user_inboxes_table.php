<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserInboxesTable extends Migration {

	public function up()
	{
		Schema::create('user_inboxes', function(Blueprint $table)
		{
			$table->increments('id_user_inboxes');
			$table->integer('id_campaign')->unsigned()->nullable()->index('fk_user_inboxes_campaigns');
			$table->integer('id_user')->unsigned()->index('fk_user_inboxes_users');
			$table->string('inboxes_subject');
			$table->text('inboxes_content', 16777215)->nullable();
			$table->dateTime('inboxes_send_at')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('user_inboxes');
	}

}
