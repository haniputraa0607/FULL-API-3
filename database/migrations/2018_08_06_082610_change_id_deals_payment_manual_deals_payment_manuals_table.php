<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdDealsPaymentManualDealsPaymentManualsTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    
    public function up()
    {
        Schema::table('deals_payment_manuals', function(Blueprint $table)
        {
            $table->renameColumn('id_transaction_payment_manual', 'id_deals_payment_manual');
        });
    }

    public function down()
    {
        Schema::table('deals_payment_manuals', function(Blueprint $table)
        {
            $table->renameColumn('id_deals_payment_manual', 'id_transaction_payment_manual');
        });
    }
}
