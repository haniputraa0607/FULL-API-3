<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdTheoryCategoryToUserHsDocument extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_hair_stylist_documents', function (Blueprint $table) {
            $table->unsignedInteger('id_theory_category')->nullable()->after('id_user_hair_stylist');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_hair_stylist_documents', function (Blueprint $table) {
            $table->dropColumn('id_theory_category');
        });
    }
}
