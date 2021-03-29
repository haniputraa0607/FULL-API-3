<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogEditBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_edit_bank_accounts', function (Blueprint $table) {
            $table->increments('id_log_edit_bank_account');
            $table->dateTime('date_time');

            $table->unsignedInteger('id_user')->nullable()->index();
            $table->unsignedInteger('id_user_franchise')->nullable()->index();
            $table->unsignedInteger('id_bank_account')->nullable()->index();
            $table->unsignedInteger('id_outlet')->nullable()->index();

            $table->text('id_outlet_old')->nullable();
            $table->text('id_outlet_new')->nullable();

            $table->integer('id_bank_name_old')->nullable();
            $table->integer('id_bank_name_new')->nullable();
            $table->string('beneficiary_name_old', 200)->nullable();
            $table->string('beneficiary_name_new', 200)->nullable();
            $table->string('beneficiary_account_old', 200)->nullable();
            $table->string('beneficiary_account_new', 200)->nullable();
            $table->string('beneficiary_alias_old', 200)->nullable();
            $table->string('beneficiary_alias_new', 200)->nullable();
            $table->string('beneficiary_email_old', 200)->nullable();
            $table->string('beneficiary_email_new', 200)->nullable();

            $table->enum('action',['create','update','delete'])->nullable();
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
        Schema::dropIfExists('log_edit_bank_accounts');
    }
}
