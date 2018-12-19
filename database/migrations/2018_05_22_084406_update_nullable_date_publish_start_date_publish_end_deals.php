<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateNullableDatePublishStartDatePublishEndDeals extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals', function(Blueprint $table) {
            $table->dateTime('deals_publish_start')->nullable()->change();
            $table->dateTime('deals_publish_end')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals', function(Blueprint $table) {
            $table->dateTime('deals_publish_start')->change();
            $table->dateTime('deals_publish_end')->change();
        });
    }
}
