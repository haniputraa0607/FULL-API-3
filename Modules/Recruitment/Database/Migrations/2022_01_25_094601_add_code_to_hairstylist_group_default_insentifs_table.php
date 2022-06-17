<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCodeToHairstylistGroupDefaultInsentifsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_group_default_insentifs', function (Blueprint $table) {
            $table->string('code')->after('id_hairstylist_group_default_insentifs')->nullable();
        });
        Schema::table('hairstylist_group_default_potongans', function (Blueprint $table) {
            $table->string('code')->after('id_hairstylist_group_default_potongans')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_group_default_potongans', function (Blueprint $table) {
            $table->dropColumn('code');
        });
        Schema::table('hairstylist_group_default_insentifs', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
}
