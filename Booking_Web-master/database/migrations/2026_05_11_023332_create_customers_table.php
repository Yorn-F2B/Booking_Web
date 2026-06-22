<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->nullOnDelete();

            $table->string('first_name');

            $table->string('last_name');

            $table->string('phone')
                ->unique();

            $table->string('cccd')
                ->nullable()
                ->unique();

            $table->string('email')
                ->nullable();

            $table->date('birthday')
                ->nullable();

            $table->enum('gender', [
                'male',
                'female',
                'other'
            ])->nullable();

            $table->text('address')
                ->nullable();

            $table->string('avatar')
                ->nullable();

            $table->text('note')
                ->nullable();

            $table->enum('status', [
                'active',
                'blacklist'
            ])->default('active');

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
