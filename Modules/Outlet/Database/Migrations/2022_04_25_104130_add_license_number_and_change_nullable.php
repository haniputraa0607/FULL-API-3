<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLicenseNumberAndChangeNullable extends Migration
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
        Schema::table('outlets', function (Blueprint $table) {
            $table->bigInteger('id_outlet_seed')->nullable()->change();
            $table->bigInteger('id_moka_outlet')->nullable()->change();
            $table->bigInteger('id_moka_account_business')->nullable()->change();
            $table->mediumText('outlet_description')->nullable()->after('outlet_name');
            $table->string('outlet_license_number')->nullable()->after('outlet_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->bigInteger('id_outlet_seed');
            $table->bigInteger('id_moka_outlet');
            $table->bigInteger('id_moka_account_business');
            $table->dropColumn('outlet_description');
            $table->dropColumn('outlet_license_number');
        });
    }
}
