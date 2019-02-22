<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOutletStatusOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlets', function(Blueprint $table) {
            $table->enum('outlet_status', ['Active', 'Inactive'])->default('Inactive')->after('outlet_longitude');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlets', function(Blueprint $table) {
            $table->dropColumn('outlet_status');
        });
    }
}
