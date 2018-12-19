<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTopupNominalsTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    
    public function up()
    {
        Schema::create('topup_nominals', function (Blueprint $table) {
            $table->increments('id_topup_nominal');
            $table->enum('type', ['Customer App', 'POS', 'Merchant App'])->default('Customer App');
            $table->decimal('nominal_bayar', 20, 0);
            $table->decimal('nominal_topup', 20, 0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('topup_nominals');
    }
}
