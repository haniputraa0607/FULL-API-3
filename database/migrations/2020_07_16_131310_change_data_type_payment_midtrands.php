<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDataTypePaymentMidtrands extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE deals_payment_midtrans CHANGE COLUMN gross_amount gross_amount DECIMAL(30,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE subscription_payment_midtrans CHANGE COLUMN gross_amount gross_amount DECIMAL(30,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE transaction_payment_midtrans CHANGE COLUMN gross_amount gross_amount DECIMAL(30,2) NOT NULL DEFAULT 0");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE deals_payment_midtrans CHANGE COLUMN gross_amount gross_amount VARCHAR(191) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE subscription_payment_midtrans CHANGE COLUMN gross_amount gross_amount VARCHAR(191) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE transaction_payment_midtrans CHANGE COLUMN gross_amount gross_amount VARCHAR(191) NOT NULL DEFAULT 0");
    }
}
