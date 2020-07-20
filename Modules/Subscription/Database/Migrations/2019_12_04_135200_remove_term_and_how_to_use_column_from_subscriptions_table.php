<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveTermAndHowToUseColumnFromSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('subscription_term');
            $table->dropColumn('subscription_how_to_use');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->text('subscription_term')->after('subscription_description');
            $table->text('subscription_how_to_use')->after('subscription_term');
        });
    }
}
