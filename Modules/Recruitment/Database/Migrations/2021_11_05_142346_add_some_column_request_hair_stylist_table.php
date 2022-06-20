<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnRequestHairStylistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
        DB::statement('ALTER TABLE `request_hair_stylists` CHANGE `status` `status` ENUM("Request","Approve","Rejected","Done Approved") default "Request";');
        Schema::table('request_hair_stylists', function (Blueprint $table) {
            $table->text('notes_om')->after('id_hs')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `request_hair_stylists` CHANGE `status` `status` ENUM("Request","Approved","Rejected") default "Request";');
        Schema::table('request_hair_stylists', function (Blueprint $table) {
            $table->dropColumn('notes_om');
        });
    }
}
