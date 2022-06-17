<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdHsToRequestHairStylist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_hair_stylists', function (Blueprint $table) {
            $table->text('id_hs')->nullable()->after('applicant');
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
            $table->dropColumn('id_hs');
        });
    }
}
