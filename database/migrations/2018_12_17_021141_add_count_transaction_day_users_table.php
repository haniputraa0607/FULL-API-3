<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCountTransactionDayUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('count_transaction_day')->unsigned()->default('0')->after('remember_token');
            $table->integer('count_transaction_week')->unsigned()->default('0')->after('count_transaction_day');
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
            $table->dropColumn('count_transaction_day');
            $table->dropColumn('count_transaction_week');
        });
    }
}
