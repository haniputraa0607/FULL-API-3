<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldIdDealsUsersInDealsPaymentManual extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_payment_manuals', function(Blueprint $table) {
            $table->unsignedInteger('id_deals_user')->after('id_deals');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_payment_manuals', function(Blueprint $table) {
            $table->dropColumn('id_deals_user');
        });
    }
}
