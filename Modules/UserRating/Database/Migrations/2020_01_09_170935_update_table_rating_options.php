<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableRatingOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rating_options', function (Blueprint $table) {
            $table->dropColumn('rule_operator');
            $table->dropColumn('value');
            $table->dropColumn('order');
        });

        DB::connection('mysql')->statement("ALTER TABLE `rating_options` ADD COLUMN `star` SET('1', '2', '3', '4', '5') NOT NULL AFTER `id_rating_option`;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rating_options', function (Blueprint $table) {
            $table->dropColumn('star');
            $table->unsignedInteger('order')->after('options')->default(0);
            $table->enum('rule_operator',['<','<=','>','>=','='])->after('id_rating_option');
            $table->unsignedInteger('value')->dropColumn('rule_operator');
        });
    }
}
