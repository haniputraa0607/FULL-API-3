<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeOutletTypeColumnToEnumFromRedirectComplexReferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::table('redirect_complex_references', function (Blueprint $table) {
        	DB::statement("ALTER TABLE redirect_complex_references MODIFY outlet_type ENUM('near me','specific') NOT NULL DEFAULT 'near me'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    	Schema::table('redirect_complex_references', function (Blueprint $table) {
    		DB::statement("ALTER TABLE redirect_complex_references MODIFY outlet_type VARCHAR(200) NOT NULL");
        });
    }
}
