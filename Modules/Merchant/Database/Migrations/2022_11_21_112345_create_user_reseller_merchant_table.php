<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserResellerMerchantTable extends Migration
{
    public function up()
    {
        Schema::create('user_reseller_merchants', function (Blueprint $table) {
            $table->bigIncrements('id_user_reseller_merchant');
            $table->unsignedInteger('id_user')->nullable();
            $table->foreign('id_user', 'fk_id_user_user_reseller_merchants')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->unsignedBigInteger('id_merchant')->nullable();
            $table->foreign('id_merchant', 'fk_user_reseller_merchants')->references('id_merchant')->on('merchants')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->unsignedBigInteger('id_merchant_grading')->nullable();
            $table->enum('reseller_merchant_status', ['Pending', 'Active', 'Inactive', 'Rejected'])->default('Pending');
            $table->unsignedInteger('id_approved')->nullable();
            $table->foreign('id_approved', 'fk_id_approved_user_reseller_merchants')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->text('notes')->nullable();
            $table->text('notes_user')->nullable();
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('user_reseller_merchants');
    }
}
