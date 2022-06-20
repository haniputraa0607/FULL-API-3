<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserHairStylist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_hair_stylist', function (Blueprint $table) {
            $table->bigIncrements('id_user_hair_stylist');
            $table->unsignedInteger('id_outlet')->nullable();
            $table->unsignedInteger('id_bank_account')->nullable();
            $table->enum('user_hair_stylist_status', ['Candidate', 'Active', 'Inactive'])->default('Candidate');
            $table->string('nickname', 100)->unique()->nullable();
            $table->string('email')->unique();
            $table->string('phone_number');
            $table->string('fullname', 100);
            $table->text('password')->nullable();
            $table->enum('level', ['Supervisor', 'Hairstylist'])->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->string('nationality')->nullable();
            $table->string('birthplace')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('religion')->nullable();
            $table->decimal('height', 10,2)->default(0);
            $table->decimal('weight', 10,2)->default(0);
            $table->string('recent_job')->nullable();
            $table->string('recent_company')->nullable();
            $table->enum('blood_type', ['A', 'B', 'AB', 'O'])->nullable();
            $table->text('recent_address')->nullable();
            $table->string('postal_code')->nullable();
            $table->enum('marital_status', ['Single', 'Married', 'Widowed', 'Divorced'])->nullable();
            $table->smallInteger('email_verified')->default(0);
            $table->smallInteger('first_update_password')->default(0);
            $table->dateTime('join_date')->nullable();
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
        Schema::dropIfExists('user_hair_stylist');
    }
}
