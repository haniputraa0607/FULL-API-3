<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNameEmailToOutletScheduleUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_schedule_updates', function (Blueprint $table) {
            $table->string('user_name')->after('user_type')->nullable();
            $table->string('user_email')->after('user_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_schedule_updates', function (Blueprint $table) {
            $table->dropColumn('user_name');
            $table->dropColumn('user_email');
        });
    }
}
