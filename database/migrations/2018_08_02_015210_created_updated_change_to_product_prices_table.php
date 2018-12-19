<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedUpdatedChangeToProductPricesTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        Schema::table('product_prices', function(Blueprint $table)
        {
            $table->dateTime('created_at')->nullable()->change();
            $table->dateTime('updated_at')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('product_prices', function(Blueprint $table)
        {
            $table->int('created_at')->nullable()->change();
            $table->int('updated_at')->nullable()->change();
        });
    }
}
