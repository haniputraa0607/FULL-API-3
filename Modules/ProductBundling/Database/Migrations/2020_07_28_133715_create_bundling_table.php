<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBundlingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bundling', function (Blueprint $table) {
            $table->increments('id_bundling');
            $table->string('bundling_name', 50);
            $table->string('image');
            $table->text('bundling_description');
            $table->decimal('price');
            $table->enum('discount_type', ['fixed', 'percentage']);
            $table->boolean('all_outlet');
            $table->integer('created_by');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('bundling');
    }
}
