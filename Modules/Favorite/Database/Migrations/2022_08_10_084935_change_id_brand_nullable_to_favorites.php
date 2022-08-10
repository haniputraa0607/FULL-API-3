<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdBrandNullableToFavorites extends Migration
{

    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropForeign('fk_favorites_id_brand');
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->integer('id_brand')->nullable(true)->default(null)->change();
            $table->text('notes')->nullable(true)->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->foreign('id_brand','fk_favorites_id_brand')->references('id_brand')->on('brands')->onDelete('CASCADE');
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->integer('id_brand')->change();
            $table->text('notes')->change();
        });
    }
}
