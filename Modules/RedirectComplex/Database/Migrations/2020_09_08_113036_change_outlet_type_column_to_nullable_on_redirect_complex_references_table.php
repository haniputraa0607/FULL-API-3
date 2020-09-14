<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeOutletTypeColumnToNullableOnRedirectComplexReferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('redirect_complex_references', function (Blueprint $table) {
            DB::statement("ALTER TABLE redirect_complex_references CHANGE COLUMN outlet_type outlet_type ENUM('near me','specific') DEFAULT NULL");
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
            DB::statement("ALTER TABLE redirect_complex_references CHANGE COLUMN outlet_type outlet_type ENUM('near me','specific') DEFAULT 'near me'");
        });
    }
}
