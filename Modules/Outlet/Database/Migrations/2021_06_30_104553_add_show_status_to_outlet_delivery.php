<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShowStatusToOutletDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('delivery_outlet', function (Blueprint $table) {
            $table->smallInteger('show_status')->default(1)->after('available_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_outlet', function (Blueprint $table) {
            $table->dropColumn('show_status');
        });
    }
}
