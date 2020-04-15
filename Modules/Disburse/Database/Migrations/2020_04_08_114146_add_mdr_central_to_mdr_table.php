<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMdrCentralToMdrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mdr', function (Blueprint $table) {
            DB::statement("ALTER TABLE mdr MODIFY mdr decimal(5,2)");
            $table->decimal('mdr_central', 5,2)->nullable()->after('mdr');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mdr', function (Blueprint $table) {
            DB::statement("ALTER TABLE mdr MODIFY mdr INTEGER");
            $table->drop('mdr_central');
        });
    }
}
