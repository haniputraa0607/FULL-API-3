<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteNotUsedInOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn('outlet_type');
            $table->dropColumn('outlet_fax');
            $table->dropColumn('outlet_open_hours');
            $table->dropColumn('outlet_close_hours');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->enum('outlet_type', ['ruko', 'mall'])->nullable()->after('outlet_name');
            $table->string('outlet_fax', 25)->nullable()->after('outlet_type');
            $table->time('outlet_open_hours')->nullable()->after('longitude');
            $table->time('outlet_close_hours')->nullable()->after('outlet_open_hours');
        });
    }
}
