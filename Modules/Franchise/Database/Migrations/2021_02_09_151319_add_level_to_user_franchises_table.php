<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLevelToUserFranchisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_franchises', function (Blueprint $table) {
            $table->enum('level', ['Super Admin','Admin'])->after('phone')->nullable();
            $table->string('name', 250)->after('phone')->nullable();
            $table->smallInteger('first_update_password')->default(0)->after('email');
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
            $table->dropColumn('name');
            $table->dropColumn('level');
            $table->dropColumn('first_update_password');
        });
    }
}
