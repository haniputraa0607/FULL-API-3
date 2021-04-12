<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUsernameToUserFranchises extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('ALTER TABLE user_franchises DROP INDEX user_franchises_email_unique;');
        Schema::table('user_franchises', function (Blueprint $table) {
            $table->string('username', '50')->nullable()->unique()->after('id_user_franchise');
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
            $table->dropColumn('username');
        });
        \DB::statement('ALTER TABLE user_franchises DROP CONSTRAINT user_franchises_email_unique UNIQUE (email);');
    }
}
