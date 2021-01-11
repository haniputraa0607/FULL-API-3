<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveSomeColumBundling extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bundling', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->dropColumn('discount_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bundling', function (Blueprint $table) {
            $table->decimal('price');
            $table->enum('discount_type', ['fixed', 'percentage']);
        });
    }
}
