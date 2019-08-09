<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCampaignsTable extends Migration {

	public function up()
	{
		Schema::create('campaigns', function(Blueprint $table)
		{
			$table->increments('id_campaign');
			$table->string('campaign_title', 191);
			$table->integer('id_user')->unsigned()->index('fk_campaigns_users');
			$table->dateTime('campaign_send_at')->nullable();
			$table->enum('campaign_generate_receipient', array('Now','Send At Time'))->default('Now');
			$table->enum('campaign_rule', array('or','and'))->default('and');
			$table->enum('campaign_media_email', array('Yes','No'))->default('No');
			$table->enum('campaign_media_sms', array('Yes','No'))->default('No');
			$table->enum('campaign_media_push', array('Yes','No'))->default('No');
			$table->enum('campaign_media_inbox', array('Yes','No'))->default('No');
			$table->integer('campaign_email_count_all')->default(0);
			$table->integer('campaign_email_count_queue')->default(0);
			$table->integer('campaign_email_count_sent')->default(0);
			$table->integer('campaign_sms_count_all')->default(0);
			$table->integer('campaign_sms_count_queue')->default(0);
			$table->integer('campaign_sms_count_sent')->default(0);
			$table->integer('campaign_push_count_all')->default(0);
			$table->integer('campaign_push_count_queue')->default(0);
			$table->integer('campaign_push_count_sent')->default(0);
			$table->integer('campaign_inbox_count')->default(0);
			$table->text('campaign_email_receipient', 16777215)->nullable();
			$table->text('campaign_sms_receipient', 16777215)->nullable();
			$table->text('campaign_push_receipient', 16777215)->nullable();
			$table->text('campaign_inbox_receipient', 16777215)->nullable();
			$table->string('campaign_email_subject')->nullable();
			$table->text('campaign_email_content', 16777215)->nullable();
			$table->text('campaign_sms_content', 65535)->nullable();
			$table->text('campaign_push_subject', 65535)->nullable();
			$table->text('campaign_push_content', 65535)->nullable();
			$table->string('campaign_push_image')->nullable();
			$table->text('campaign_push_clickto', 65535)->nullable();
			$table->string('campaign_push_link')->nullable();
			$table->string('campaign_push_id_reference')->nullable();
			$table->text('campaign_inbox_subject', 65535)->nullable();
			$table->text('campaign_inbox_content', 65535)->nullable();
			$table->enum('campaign_is_sent', array('Yes','No'))->default('No');
			$table->text('campaign_description', 65535)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('campaigns');
	}

}
