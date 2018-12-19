<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDateTimeToTransactionsTable extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    
    public function up()
    {
        Schema::table('transactions', function(Blueprint $table)
        {
            $table->dateTime('transaction_date')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('transactions', function(Blueprint $table)
        {
            $table->date('transaction_date')->nullable()->change();
        });
    }
}
