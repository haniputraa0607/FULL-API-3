<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOutletSpecialStatusToOutlets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->decimal('outlet_special_fee', 5,2)->default(0)->after('status_franchise');
            $table->smallInteger('outlet_special_status')->default(0)->after('status_franchise');
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
            $table->dropColumn('outlet_special_fee');
            $table->dropColumn('outlet_special_status');
        });
    }
}
