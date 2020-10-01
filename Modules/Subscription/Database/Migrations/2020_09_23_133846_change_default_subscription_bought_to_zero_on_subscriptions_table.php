<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDefaultSubscriptionBoughtToZeroOnSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function __construct()
	{
	    DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
	}

    public function up()
    {
        Schema::table('subscriptions', function ($table) {
        	$table->integer('subscription_bought')->nullable(false)->default(0)->change();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    	Schema::table('subscriptions', function ($table) {
        	$table->integer('subscription_bought')->nullable(true)->default(null)->change();
		});
    }
}
