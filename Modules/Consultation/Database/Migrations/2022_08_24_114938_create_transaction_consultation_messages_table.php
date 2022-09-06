<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionConsultationMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_consultation_messages', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_consultation_message');
            $table->integer('id_transaction_consultation');
            $table->string('id_message');
            $table->string('direction');
            $table->string('content_type');
            $table->text('text');
            $table->text('url');
            $table->text('caption');
            $table->datetime('created_at_infobip');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql2')->dropIfExists('log_shipper');
    }
}
