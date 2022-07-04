<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXenditAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xendit_accounts', function (Blueprint $table) {
            $table->bigIncrements('id_xendit_account');
            $table->string('xendit_id')->nullable();
            $table->enum('type', ['MANAGED', 'OWNED'])->nullable();
            $table->string('email')->nullable();
            $table->text('public_profile')->nullable();
            $table->string('country')->nullable();
            $table->enum('status', ['INVITED', 'REGISTERED', 'AWAITING_DOCS'])->nullable();
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
        Schema::dropIfExists('xendit_accounts');
    }
}
