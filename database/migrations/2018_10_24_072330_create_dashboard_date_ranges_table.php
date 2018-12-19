<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDashboardDateRangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dashboard_date_ranges', function (Blueprint $table) {
            $table->increments('id_dashboard_date_range');
            $table->unsignedInteger('id_user');
            $table->string('default_date_range');
            $table->timestamps();

            $table->foreign('id_user', 'fk_dashboard_date_ranges_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dashboard_date_ranges');
    }
}
