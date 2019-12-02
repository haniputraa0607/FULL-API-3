<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserFeedbacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_feedbacks', function (Blueprint $table) {
            $table->increments('id_user_feedback');
            $table->unsignedInteger('id_outlet');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_rating_item')->nullable();
            $table->unsignedInteger('rating_item_text');
            $table->text('notes')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();

            $table->foreign('id_outlet')->on('outlets')->references('id_outlet')->onDelete('cascade');
            $table->foreign('id_user')->on('users')->references('id')->onDelete('cascade');
            $table->foreign('id_transaction')->on('transactions')->references('id_transaction')->onDelete('cascade');
            $table->foreign('id_rating_item')->on('rating_items')->references('id_rating_item')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_feedbacks');
    }
}
