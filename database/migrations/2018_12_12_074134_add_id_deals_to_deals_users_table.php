<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdDealsToDealsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_users', function (Blueprint $table) {
            $table->integer('id_deals')->comment('for deals subscription voucher pay')->unsigned()->nullable()->after('id_deals_voucher');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_users', function (Blueprint $table) {
            $table->dropColumn('id_deals');
        });
    }
}
