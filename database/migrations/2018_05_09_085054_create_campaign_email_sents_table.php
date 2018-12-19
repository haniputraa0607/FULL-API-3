<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCampaignEmailSentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('campaign_email_sents', function(Blueprint $table)
		{
			$table->increments('id_campaign_email_sent');
			$table->integer('id_campaign')->unsigned()->index('fk_campaign_email_sents_campaigns');
			$table->string('email_sent_to');
			$table->string('email_sent_subject');
			$table->text('email_sent_message', 16777215);
			$table->dateTime('email_sent_send_at')->nullable();
			$table->boolean('email_sent_is_read')->nullable();
			$table->boolean('email_sent_is_clicked')->nullable();
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('campaign_email_sents');
	}

}
