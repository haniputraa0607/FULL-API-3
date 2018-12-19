<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeBenefitPromoIdUsersMembershipsTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    
    public function up()
    {
        Schema::table('users_memberships', function(Blueprint $table)
        {
            $table->string('benefit_promo_id', 50)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('users_memberships', function(Blueprint $table)
        {
            $table->integer('benefit_promo_id')->unsigned()->nullable()->change();
        });
    }
}
