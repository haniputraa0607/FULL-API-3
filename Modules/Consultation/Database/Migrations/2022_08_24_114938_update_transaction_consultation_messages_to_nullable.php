<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTransactionConsultationMessagesToNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_consultation_messages', function (Blueprint $table) {
            $table->text('text')->nullable()->change();
            $table->text('url')->nullable()->change();
            $table->text('caption')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_consultation_messages', function (Blueprint $table) {
            $table->text('text')->change();
            $table->text('url')->change();
            $table->text('caption')->change();
        });
    }
}
