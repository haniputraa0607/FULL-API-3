<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRatingValueToUserFeedbackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_feedbacks', function (Blueprint $table) {
            $table->tinyInteger('rating_value')->after('id_transaction');
            $table->dropForeign('user_feedbacks_id_rating_item_foreign');
            $table->dropColumn('id_rating_item');
            $table->dropColumn('rating_item_text');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_feedbacks', function (Blueprint $table) {
            $table->dropColumn('rating_value');
            $table->unsignedInteger('id_rating_item')->nullable()->after('id_transaction');
            $table->string('rating_item_text')->after('id_rating_item');
            $table->foreign('id_rating_item')->on('rating_items')->references('id_rating_item')->onDelete('set null');
        });
    }
}
