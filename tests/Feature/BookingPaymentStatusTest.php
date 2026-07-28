<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Customer;
use App\Models\RoomCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private RoomCategory $roomCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '0123456789',
            'email' => 'john.doe@example.com',
            'status' => 'active',
        ]);

        $this->roomCategory = RoomCategory::create([
            'name' => 'Deluxe Room',
            'price' => 1000000,
            'adult_capacity' => 2,
            'child_capacity' => 1,
            'area' => 35,
            'bed_count' => 1,
            'status' => 'active',
        ]);
    }

    public function test_booking_payment_status_defaults_to_unpaid_on_creation(): void
    {
        $booking = Booking::create([
            'booking_code' => 'BK_TEST_001',
            'customer_id' => $this->customer->id,
            'room_category_id' => $this->roomCategory->id,
            'check_in_date' => '2026-08-01',
            'check_out_date' => '2026-08-03',
            'check_in_at' => '2026-08-01 14:00:00',
            'check_out_at' => '2026-08-03 12:00:00',
            'estimated_total' => 2000000,
            'deposit_amount' => 500000,
            'payment_status' => 'paid', // Fake manual value to test that it is overridden on first update / if transaction sum is checked
        ]);

        // When a new booking is saved, the saving listener checks payments.
        // Since there are NO booking payments in the DB yet, it is saved as unpaid with deposit_amount = 0
        $booking->save();

        $this->assertEquals('unpaid', $booking->payment_status);
        $this->assertEquals(0, $booking->deposit_amount);
    }

    public function test_booking_payment_status_recalculates_on_successful_payment_creation(): void
    {
        $booking = Booking::create([
            'booking_code' => 'BK_TEST_002',
            'customer_id' => $this->customer->id,
            'room_category_id' => $this->roomCategory->id,
            'check_in_date' => '2026-08-01',
            'check_out_date' => '2026-08-03',
            'check_in_at' => '2026-08-01 14:00:00',
            'check_out_at' => '2026-08-03 12:00:00',
            'estimated_total' => 2000000,
            'payment_status' => 'unpaid',
        ]);

        $this->assertEquals('unpaid', $booking->payment_status);

        // Add a successful payment (partial payment)
        $payment1 = BookingPayment::create([
            'booking_id' => $booking->id,
            'amount' => 800000,
            'status' => 'success',
            'provider' => 'cash',
            'txn_ref' => 'TXN_001',
            'payment_type' => 'deposit_30',
        ]);

        $booking->refresh();
        $this->assertEquals('partial', $booking->payment_status);
        $this->assertEquals(800000, (float) $booking->deposit_amount);

        // Add another successful payment to cover the remaining total
        $payment2 = BookingPayment::create([
            'booking_id' => $booking->id,
            'amount' => 1200000,
            'status' => 'success',
            'provider' => 'bank_transfer',
            'txn_ref' => 'TXN_002',
            'payment_type' => 'full_100',
        ]);

        $booking->refresh();
        $this->assertEquals('paid', $booking->payment_status);
        $this->assertEquals(2000000, (float) $booking->deposit_amount);
    }

    public function test_booking_payment_status_ignores_pending_or_failed_payments(): void
    {
        $booking = Booking::create([
            'booking_code' => 'BK_TEST_003',
            'customer_id' => $this->customer->id,
            'room_category_id' => $this->roomCategory->id,
            'check_in_date' => '2026-08-01',
            'check_out_date' => '2026-08-03',
            'check_in_at' => '2026-08-01 14:00:00',
            'check_out_at' => '2026-08-03 12:00:00',
            'estimated_total' => 2000000,
            'payment_status' => 'unpaid',
        ]);

        // Add a pending payment
        BookingPayment::create([
            'booking_id' => $booking->id,
            'amount' => 1000000,
            'status' => 'pending',
            'provider' => 'vnpay',
            'txn_ref' => 'TXN_PENDING',
            'payment_type' => 'deposit_30',
        ]);

        $booking->refresh();
        $this->assertEquals('unpaid', $booking->payment_status);
        $this->assertEquals(0, (float) $booking->deposit_amount);

        // Add a failed payment
        BookingPayment::create([
            'booking_id' => $booking->id,
            'amount' => 2000000,
            'status' => 'failed',
            'provider' => 'vnpay',
            'txn_ref' => 'TXN_FAILED',
            'payment_type' => 'full_100',
        ]);

        $booking->refresh();
        $this->assertEquals('unpaid', $booking->payment_status);
        $this->assertEquals(0, (float) $booking->deposit_amount);
    }

    public function test_booking_payment_status_recalculates_on_payment_deletion(): void
    {
        $booking = Booking::create([
            'booking_code' => 'BK_TEST_004',
            'customer_id' => $this->customer->id,
            'room_category_id' => $this->roomCategory->id,
            'check_in_date' => '2026-08-01',
            'check_out_date' => '2026-08-03',
            'check_in_at' => '2026-08-01 14:00:00',
            'check_out_at' => '2026-08-03 12:00:00',
            'estimated_total' => 2000000,
            'payment_status' => 'unpaid',
        ]);

        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'amount' => 2000000,
            'status' => 'success',
            'provider' => 'cash',
            'txn_ref' => 'TXN_TO_DELETE',
            'payment_type' => 'full_100',
        ]);

        $booking->refresh();
        $this->assertEquals('paid', $booking->payment_status);
        $this->assertEquals(2000000, (float) $booking->deposit_amount);

        // Delete the payment
        $payment->delete();

        $booking->refresh();
        $this->assertEquals('unpaid', $booking->payment_status);
        $this->assertEquals(0, (float) $booking->deposit_amount);
    }

    public function test_booking_payment_status_overrides_manual_tampering(): void
    {
        $booking = Booking::create([
            'booking_code' => 'BK_TEST_005',
            'customer_id' => $this->customer->id,
            'room_category_id' => $this->roomCategory->id,
            'check_in_date' => '2026-08-01',
            'check_out_date' => '2026-08-03',
            'check_in_at' => '2026-08-01 14:00:00',
            'check_out_at' => '2026-08-03 12:00:00',
            'estimated_total' => 2000000,
            'payment_status' => 'unpaid',
        ]);

        // Manual tampering via code
        $booking->payment_status = 'paid';
        $booking->deposit_amount = 2000000;
        $booking->save();

        // The save hook should recalculate and override the manual values
        $this->assertEquals('unpaid', $booking->payment_status);
        $this->assertEquals(0, (float) $booking->deposit_amount);
    }
}
