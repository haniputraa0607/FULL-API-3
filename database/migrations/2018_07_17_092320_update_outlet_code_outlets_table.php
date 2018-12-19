<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateOutletCodeOutletsTable extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    
    public function up()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->string('outlet_code', 3)->unique()->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropUnique('outlets_outlet_code_unique');
            $table->string('outlet_code', 10)->nullable()->change();
        });
    }
}
