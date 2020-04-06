<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdUserFranchiseSeedSeedToUserFranchisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_franchises', function (Blueprint $table) {
            $table->integer('id_user_franchisee_seed')->after('id_user_franchisee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_franchises', function (Blueprint $table) {

        });
    }
}
