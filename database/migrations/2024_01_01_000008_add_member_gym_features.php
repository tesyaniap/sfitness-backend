<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom member ke users
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('birth_place')->nullable()->after('birth_date');
            $table->string('religion')->nullable()->after('birth_place');
            $table->string('occupation')->nullable()->after('religion');
            $table->text('address')->nullable()->after('occupation');
            $table->string('member_number')->nullable()->unique()->after('address');
        });

        // Paket member
        Schema::create('member_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Single Visit, Member 4x, Member 8x
            $table->enum('type', ['single', '4x', '8x']);
            $table->integer('visit_quota')->default(1); // 1, 4, 8
            $table->integer('active_days')->default(0); // 0 = tidak ada masa aktif (single)
            $table->timestamps();
        });

        // Membership member (paket yang dibeli)
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('package_id')->constrained('member_packages')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->integer('visit_quota');       // total visit dibeli
            $table->integer('visit_used')->default(0);  // sudah dipakai
            $table->integer('visit_remaining');   // sisa
            $table->date('start_date');
            $table->date('expired_date')->nullable(); // null = single visit
            $table->enum('status', ['active', 'expired', 'used_up'])->default('active');
            $table->enum('payment_method', ['cash', 'qris', 'transfer', 'midtrans'])->default('cash');
            $table->enum('payment_status', ['paid', 'pending'])->default('paid');
            $table->string('snap_token')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Absensi kelas
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->foreignId('membership_id')->nullable()->constrained()->nullOnDelete(); // null = single visit
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name')->nullable(); // untuk single visit non-member
            $table->enum('type', ['member', 'single_visit']);
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->timestamps();
        });

        // Tambah harga beli ke products
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('buy_price', 10, 2)->default(0)->after('price');
        });

        // Tambah info pembeli ke transactions (untuk non-member)
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('buyer_name')->nullable()->after('user_id'); // nama non-member
            $table->enum('buyer_type', ['member', 'non_member'])->default('member')->after('buyer_name');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', fn($t) => $t->dropColumn(['buyer_name', 'buyer_type']));
        Schema::table('products', fn($t) => $t->dropColumn('buy_price'));
        Schema::dropIfExists('attendance_members');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('member_packages');
        Schema::table('users', fn($t) => $t->dropColumn([
            'whatsapp', 'birth_place', 'religion', 'occupation', 'address', 'member_number'
        ]));
    }
};
