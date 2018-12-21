<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateOutletScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_schedules', function (Blueprint $table) {
            $table->time('open')->nullable(true)->change();
            $table->time('close')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_schedules', function (Blueprint $table) {
            $table->time('open')->nullable(false)->change();
            $table->time('close')->nullable(false)->change();
        });
    }
}
