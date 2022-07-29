<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeToUserPromo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_promos', function (Blueprint $table) {
            $table->enum('promo_use_in', ['Consultation','Product'])->nullable()->default('Product')->after('promo_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_promos', function (Blueprint $table) {
            $table->dropColumn('promo_use_in');
        });
    }
}
