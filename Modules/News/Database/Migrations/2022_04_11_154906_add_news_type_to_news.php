<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewsTypeToNews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('news', function (Blueprint $table) {
            $table->enum('news_type', ['video', 'article', 'online_class'])->nullable()->after('news_slug');
            $table->string('news_by')->nullable()->after('news_expired_date');
            $table->string('news_button_text', 50)->nullable()->after('news_form_success_message');
            $table->string('news_button_link', 250)->nullable()->after('news_form_success_message');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('news_type');
            $table->dropColumn('news_by');
            $table->dropColumn('news_button_text');
            $table->dropColumn('news_button_link');
        });
    }
}
