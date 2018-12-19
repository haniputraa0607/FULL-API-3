<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdUserPromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		Schema::table('promotions', function (Blueprint $table) {
            $table->integer('id_user')->unsigned()->after('id_Promotion')->index('fk_users_promotions');
        });
		
		Schema::table('promotions', function (Blueprint $table) {
			$table->foreign('id_user', 'fk_users_promotions')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotions', function (Blueprint $table) {
			$table->dropForeign('fk_users_promotions');
			$table->dropColumn('id_user');
		});
    }
}
