<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdTransactionConsultationToUserRatingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_rating_logs', function (Blueprint $table) {
        	$table->unsignedInteger('id_transaction_consultation')->nullable()->after('id_transaction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_rating_logs', function (Blueprint $table) {
        	$table->dropColumn('id_transaction_consultation');
        });
    }
}
