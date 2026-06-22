<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('promotion_type');
            $table->string('discount_type');
            $table->decimal('discount_value', 12, 2);
            $table->decimal('max_discount_amount', 12, 2)->default(0);
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_to')->nullable();
            $table->date('stay_from')->nullable();
            $table->date('stay_to')->nullable();
            $table->decimal('min_booking_amount', 12, 2)->default(0);
            $table->integer('min_nights')->default(0);
            $table->integer('min_rooms')->default(0);
            $table->integer('min_completed_bookings')->default(0);
            $table->decimal('min_total_spent', 12, 2)->default(0);
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->integer('per_customer_limit')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('user_can_apply')->default(true);
            $table->boolean('admin_can_apply')->default(true);
            $table->boolean('requires_note')->default(false);
            $table->boolean('is_stackable')->default(false);
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('promotion_service_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promotion_id');
            $table->unsignedBigInteger('service_id');
            $table->string('discount_type');
            $table->decimal('discount_value', 12, 2);
            $table->integer('quantity')->default(1);
            $table->boolean('auto_add_service')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
        });

        Schema::create('booking_promotions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('promotion_id')->nullable();
            $table->string('code_snapshot');
            $table->string('promotion_type_snapshot');
            $table->string('discount_type_snapshot');
            $table->decimal('discount_value_snapshot', 12, 2);
            $table->decimal('money_discount_amount', 12, 2)->default(0);
            $table->decimal('service_discount_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->unsignedBigInteger('applied_by')->nullable();
            $table->string('applied_channel')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('promotion_id')->references('id')->on('promotions')->nullOnDelete();
            $table->foreign('applied_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('booking_promotion_service_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('booking_promotion_id');
            $table->unsignedBigInteger('promotion_id')->nullable();
            $table->unsignedBigInteger('promotion_service_offer_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('code_snapshot');
            $table->string('service_name_snapshot');
            $table->string('service_unit_snapshot')->nullable();
            $table->decimal('service_price_snapshot', 12, 2);
            $table->string('discount_type_snapshot');
            $table->decimal('discount_value_snapshot', 12, 2);
            $table->integer('quantity');
            $table->decimal('original_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2);
            $table->decimal('final_amount', 12, 2);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('booking_promotion_id', 'bpso_bp_id_foreign')->references('id')->on('booking_promotions')->cascadeOnDelete();
            $table->foreign('promotion_id')->references('id')->on('promotions')->nullOnDelete();
            $table->foreign('promotion_service_offer_id', 'bpso_pso_id_foreign')->references('id')->on('promotion_service_offers')->nullOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_promotion_service_offers');
        Schema::dropIfExists('booking_promotions');
        Schema::dropIfExists('promotion_service_offers');
        Schema::dropIfExists('promotions');
    }
};
