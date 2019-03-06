<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeBenefitPromoIdMembershipsTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->text('benefit_promo_id')->nullable()->change();
        });
        Schema::table('users_memberships', function (Blueprint $table) {
            $table->text('benefit_promo_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('benefit_promo_id', 20)->nullable()->change();
        });
        Schema::table('users_memberships', function (Blueprint $table) {
            $table->string('benefit_promo_id', 20)->nullable()->change();
        });
    }
}
