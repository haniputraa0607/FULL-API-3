<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldTypeAdvertTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adverts', function(Blueprint $table) {
            $table->string('type', 20)->after('id_advert')->nullable();
            $table->tinyInteger('order')->after('page')->nullable();
            $table->dropColumn('img_top');
            $table->dropColumn('img_bottom');
            $table->dropColumn('txt_top');
            $table->dropColumn('txt_bottom');
            $table->text('value')->nullable()->after('page');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('adverts', function(Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('order');
            $table->dropColumn('value');

            $table->string('img_top', 100)->nullable();
            $table->string('img_bottom', 100)->nullable();
            $table->text('txt_top')->nullable();
            $table->text('txt_bottom')->nullable();
        });
    }
}
