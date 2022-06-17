<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserRatingSummariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_rating_summaries', function (Blueprint $table) {
            $table->increments('id_user_rating_summary');
            $table->unsignedBigInteger('id_doctor')->nullable();
        	$table->integer('id_outlet')->nullable();
            $table->enum('summary_type', array('rating_value','option_value'));
			$table->string('key');
			$table->integer('value');
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
        Schema::dropIfExists('user_rating_summaries');
    }
}
