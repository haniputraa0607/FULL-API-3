<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistIncomesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_incomes', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_income');
            $table->unsignedBigInteger('id_user_hair_stylist');
            $table->enum('type', ['middle', 'end']);
            $table->date('periode');
            $table->date('start_date');
            $table->date('end_date');
            $table->dateTime('completed_at')->nullable();
            $table->enum('status', ['Draft', 'Pending', 'Completed', 'Cancelled'])->default('Draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('amount')->nullable();
            $table->timestamps();

            $table->foreign('id_user_hair_stylist')->references('id_user_hair_stylist')->on('user_hair_stylist')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hairstylist_incomes');
    }
}
