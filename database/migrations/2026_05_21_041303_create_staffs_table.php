<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staffs', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('full_name', 100);

            $table->string('phone', 20)
                ->nullable()
                ->unique();

            $table->string('cccd', 20)
                ->nullable()
                ->unique();

            $table->date('birthday')->nullable();

            $table->enum('gender', [
                'male',
                'female',
                'other'
            ])->nullable();

            $table->text('address')->nullable();

            $table->string('position', 100)->nullable();

            $table->decimal('salary', 12, 2)
                ->default(0);

            $table->date('hire_date')->nullable();

            $table->string('avatar')->nullable();

            $table->enum('work_status', [
                'working',
                'resigned',
                'temporary_leave'
            ])->default('working');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staffs');
    }
};