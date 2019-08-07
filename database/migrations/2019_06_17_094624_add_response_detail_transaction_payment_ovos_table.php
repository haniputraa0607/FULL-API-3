<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddResponseDetailTransactionPaymentOvosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_ovos', function (Blueprint $table) {
            $table->string('response_detail')->nullable()->after('response_code');
            $table->text('response_description')->nullable()->after('response_detail');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payment_ovos', function (Blueprint $table) {
            $table->dropColumn('response_detail');
            $table->dropColumn('response_description');
        });
    }
}
