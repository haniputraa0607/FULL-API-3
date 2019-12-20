<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNullablePrefixCodeToPromoCampaigns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::getConnection()->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        Schema::table('promo_campaigns', function (Blueprint $table) {
            $table->string('prefix_code', 15)->nullable()->change();
            $table->integer('number_last_code')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::getConnection()->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        Schema::table('promo_campaigns', function (Blueprint $table) { 
			$table->string('prefix_code', 15)->nullable(false)->change();
            $table->integer('number_last_code')->nullable(false)->change();
		});
    }
}
