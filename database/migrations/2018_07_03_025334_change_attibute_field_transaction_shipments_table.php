<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAttibuteFieldTransactionShipmentsTable extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    
    public function up()
    {
        Schema::table('transaction_shipments', function (Blueprint $table) {
            $table->string('short_link')->nullable(true)->change();
        });
    }

    public function down()
    {
        Schema::table('transaction_shipments', function (Blueprint $table) {
            $table->string('short_link')->nullable(false)->change();
        });
    }
}
