<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdHairstylistCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
            $table->unsignedInteger('id_hairstylist_category')->nullable()->after('id_bank_account');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
            $table->dropColumn('id_hairstylist_category');
        });
    }
}
