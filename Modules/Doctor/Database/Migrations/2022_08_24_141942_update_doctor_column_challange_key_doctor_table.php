<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDoctorColumnChallangeKeyDoctorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('doctors', 'challange_key'))
        {
            Schema::table('doctors', function (Blueprint $table)
            {
                $table->dropColumn('challange_key');
            });
        }
    }

    /**
     * Reverse the migrations. 
     *
     * @return void
     */
    public function down()
    {
        Schema::table('doctors', function (Blueprint $table) {
			$table->string('challange_key')->nullable();
        });
    }
}
