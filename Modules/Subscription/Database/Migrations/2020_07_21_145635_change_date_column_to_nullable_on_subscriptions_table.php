<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDateColumnToNullableOnSubscriptionsTable extends Migration
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
    	Schema::table('subscriptions', function (Blueprint $table) {
	        $table->dateTime('subscription_start')->nullable()->change();
	        $table->dateTime('subscription_end')->nullable()->change();
	        $table->dateTime('subscription_publish_start')->nullable()->change();
	        $table->dateTime('subscription_publish_end')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    	Schema::table('subscriptions', function (Blueprint $table) {
	        $table->dateTime('subscription_start')->nullable(false)->change();
	        $table->dateTime('subscription_end')->nullable(false)->change();
	        $table->dateTime('subscription_publish_start')->nullable(false)->change();
	        $table->dateTime('subscription_publish_end')->nullable(false)->change();
        });
    }
}
