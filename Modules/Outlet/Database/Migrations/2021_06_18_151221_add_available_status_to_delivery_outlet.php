<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAvailableStatusToDeliveryOutlet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('delivery_outlet', function (Blueprint $table) {
            $table->smallInteger('available_status')->default(1)->after('code');
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
            $table->dropColumn('available_status');
        });
    }
}
