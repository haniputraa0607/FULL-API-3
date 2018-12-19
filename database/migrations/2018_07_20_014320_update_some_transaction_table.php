<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSomeTransactionTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        Schema::table('transaction_pickups', function (Blueprint $table) {
            $table->unsignedInteger('id_admin_outlet_receive')->nullable()->after('taken_at');
            $table->unsignedInteger('id_admin_outlet_taken')->nullable()->after('id_admin_outlet_receive');

            $table->index(["id_admin_outlet_receive"], 'fk_id_admin_outlet_receive_transaction_pickup_idx');
            
            $table->foreign('id_admin_outlet_receive', 'fk_id_admin_outlet_receive_transaction_pickup_idx')
                ->references('id_user_outlet')->on('user_outlets')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index(["id_admin_outlet_taken"], 'fk_id_admin_outlet_taken_transaction_pickup_idx');
            
            $table->foreign('id_admin_outlet_taken', 'fk_id_admin_outlet_taken_transaction_pickup_idx')
                ->references('id_user_outlet')->on('user_outlets')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });

        Schema::table('transaction_shipments', function (Blueprint $table) {
            $table->unsignedInteger('order_id')->nullable()->after('id_transaction');
            $table->unsignedInteger('id_admin_outlet_receive')->nullable()->after('receive_at');
            $table->dateTime('send_at')->nullable()->after('id_admin_outlet_receive');
            $table->unsignedInteger('id_admin_outlet_send')->nullable()->after('send_at');

            $table->index(["id_admin_outlet_receive"], 'fk_id_admin_outlet_receive_transaction_delivery_idx');
            
            $table->foreign('id_admin_outlet_receive', 'fk_id_admin_outlet_receive_transaction_delivery_idx')
                ->references('id_user_outlet')->on('user_outlets')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index(["id_admin_outlet_send"], 'fk_id_admin_outlet_send_transaction_delivery_idx');
            
            $table->foreign('id_admin_outlet_send', 'fk_id_admin_outlet_send_transaction_delivery_idx')
                ->references('id_user_outlet')->on('user_outlets')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('transaction_shipments', function (Blueprint $table) {
            $table->dropForeign('fk_id_admin_outlet_send_transaction_delivery_idx');
            $table->dropIndex('fk_id_admin_outlet_send_transaction_delivery_idx');

            $table->dropForeign('fk_id_admin_outlet_receive_transaction_delivery_idx');
            $table->dropIndex('fk_id_admin_outlet_receive_transaction_delivery_idx');

            $table->dropColumn('id_admin_outlet_send');
            $table->dropColumn('send_at');
            $table->dropColumn('id_admin_outlet_receive');
            $table->dropColumn('order_id');
        });

        Schema::table('transaction_pickups', function (Blueprint $table) {
            $table->dropForeign('fk_id_admin_outlet_taken_transaction_pickup_idx');
            $table->dropIndex('fk_id_admin_outlet_taken_transaction_pickup_idx');

            $table->dropForeign('fk_id_admin_outlet_receive_transaction_pickup_idx');
            $table->dropIndex('fk_id_admin_outlet_receive_transaction_pickup_idx');

            $table->dropColumn('id_admin_outlet_taken');
            $table->dropColumn('id_admin_outlet_receive');
        });
    }
}
