<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // zumba, yoga, pilates, etc
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('schedule_at');
            $table->integer('duration_minutes')->default(60);
            $table->integer('quota');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('location')->nullable();
            $table->enum('status', ['active', 'cancelled', 'completed'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
