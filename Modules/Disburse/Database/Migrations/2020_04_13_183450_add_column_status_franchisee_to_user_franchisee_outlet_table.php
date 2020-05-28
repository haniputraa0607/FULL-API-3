<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnStatusFranchiseeToUserFranchiseeOutletTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_franchise_outlet', function (Blueprint $table) {
            $table->integer('status_franchise')->nullable()->after('id_outlet');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_franchise_outlet', function (Blueprint $table) {
            $table->dropColumn('status_franchise');
        });
    }
}
