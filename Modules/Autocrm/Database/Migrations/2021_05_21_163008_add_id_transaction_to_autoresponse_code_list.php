<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdTransactionToAutoresponseCodeList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('autoresponse_code_list', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction')->nullable()->after('id_user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('autoresponse_code_list', function (Blueprint $table) {
            $table->dropColumn('id_transaction');
        });
    }
}
