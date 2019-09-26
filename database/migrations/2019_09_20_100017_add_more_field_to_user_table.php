<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMoreFieldToUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('celebrate',50)->nullable()->after('id_city');
            $table->string('job',50)->nullable()->after('id_city');
            $table->string('address',255)->nullable()->after('id_city');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('celebrate');
            $table->dropColumn('job');
            $table->dropColumn('address');
        });
    }
}
