<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPasswordDefaultPlainTextToUserFranchisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_franchises', function (Blueprint $table) {
            $table->string('password_default_plain_text')->nullable()->after('password');
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
            $table->dropColumn('password_default_plain_text');
        });
    }
}
