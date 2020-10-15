<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFlagInvalidToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transaction_flag_invalid', ['Valid', 'Invalid'])->nullable()->after('distance_customer');
            $table->text('image_invalid_flag')->nullable()->after('distance_customer');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('transaction_flag_invalid');
            $table->dropColumn('image_invalid_flag');
        });
    }
}
