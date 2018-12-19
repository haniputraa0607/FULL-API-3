<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCampaignsTable extends Migration {

	public function up()
	{
		Schema::table('campaigns', function(Blueprint $table)
		{
			$table->foreign('id_user', 'fk_campaigns_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('campaigns', function(Blueprint $table)
		{
			$table->dropForeign('fk_campaigns_users');
		});
	}

}
