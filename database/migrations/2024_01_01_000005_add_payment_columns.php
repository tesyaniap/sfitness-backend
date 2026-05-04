<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('snap_token')->nullable()->after('payment_method');
            $table->string('midtrans_order_id')->nullable()->after('snap_token');
            $table->string('payment_type')->nullable()->after('midtrans_order_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('notes');
            $table->string('snap_token')->nullable()->after('price');
            $table->string('midtrans_order_id')->nullable()->after('snap_token');
            $table->enum('payment_status', ['unpaid', 'paid', 'failed'])->default('unpaid')->after('midtrans_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['snap_token', 'midtrans_order_id', 'payment_type']);
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['price', 'snap_token', 'midtrans_order_id', 'payment_status']);
        });
    }
};
