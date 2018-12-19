<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOutletTypeOutlet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlets', function(Blueprint $table) {
            $table->enum('outlet_type', ['ruko', 'mall'])->nullable()->after('outlet_name');
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
            $table->dropColumn('outlet_type');
        });
    }
}
