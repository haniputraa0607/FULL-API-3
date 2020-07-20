<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeFieldTypeNominalInDisburse extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse', function (Blueprint $table) {
            DB::statement("ALTER TABLE disburse MODIFY disburse_nominal decimal(30,4)");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disburse', function (Blueprint $table) {
            DB::statement("ALTER TABLE disburse MODIFY disburse_nominal INTEGER");
        });
    }
}
