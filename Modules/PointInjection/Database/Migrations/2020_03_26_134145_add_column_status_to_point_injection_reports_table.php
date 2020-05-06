<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnStatusToPointInjectionReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('point_injection_reports', function (Blueprint $table) {
            $table->enum('status', ['Pending','Success', 'Failed'])->after('point')->nullable('Pending');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('point_injection_reports', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
