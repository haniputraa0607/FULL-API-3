<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteAndAddSomeColumnToRequestHairStylistTable extends Migration
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
        Schema::table('request_hair_stylists', function (Blueprint $table) {
            $table->dropColumn('applicant');
            $table->dropColumn('applicant_phone');
            $table->integer('id_user')->after('status')->unsigned()->nullable();
            $table->foreign('id_user', 'fk_request_hair_user_applicant')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_hair_stylists', function (Blueprint $table) {
            $table->dropForeign('fk_request_hair_user_applicant');
            $table->dropIndex('fk_request_hair_user_applicant');
        });
        Schema::table('request_hair_stylists', function (Blueprint $table) {
            $table->dropColumn('id_user');
            $table->string('applicant')->after('status')->nullable();
            $table->text('applicant_phone')->after('applicant')->nullable();
        });
    }
}
