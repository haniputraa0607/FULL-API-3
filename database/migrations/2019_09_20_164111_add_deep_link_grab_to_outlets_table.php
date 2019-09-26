<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeepLinkGrabToOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('outlets', function (Blueprint $table) {
            $table->text('deep_link_grab')->nullable()->after('deep_link');
            $table->text('deep_link_gojek')->nullable()->after('deep_link');
            $table->dropColumn('deep_link');
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
            $table->dropColumn('deep_link_grab');
            $table->dropColumn('deep_link_gojek');
            $table->text('deep_link')->nullable()->after('outlet_status');
        });
    }
}
