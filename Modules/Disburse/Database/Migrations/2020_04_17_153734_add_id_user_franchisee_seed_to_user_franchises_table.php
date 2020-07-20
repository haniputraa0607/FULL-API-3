<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdUserFranchiseeSeedToUserFranchisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_franchises', function (Blueprint $table) {
            $table->bigInteger('id_user_franchise_seed')->nullable()->default(12);
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
