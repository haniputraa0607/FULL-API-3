<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeFieldPromotionContentShortenLinks extends Migration
{
    public function up()
    {
        Schema::table('promotion_content_shorten_links', function(Blueprint $table) {
            $table->integer('link_clicked')->default(0)->after('type');
            $table->integer('link_unique_clicked')->default(0)->after('link_clicked');
        });
    }

    public function down()
    {
        Schema::table('promotion_sents', function(Blueprint $table) {
            $table->dropColumn('link_clicked');
            $table->dropColumn('link_unique_clicked');
        });
    } 

}
