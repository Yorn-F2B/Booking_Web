<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private RoomCategory $roomCategory;
    private Room $room;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '0987654321',
            'email' => 'jane.doe@example.com',
            'status' => 'active',
        ]);

        $this->roomCategory = RoomCategory::create([
            'name' => 'Suite Room',
            'price' => 2000000,
            'adult_capacity' => 2,
            'child_capacity' => 2,
            'area' => 50,
            'bed_count' => 2,
            'status' => 'active',
        ]);

        $this->room = Room::create([
            'room_number' => '301',
            'room_category_id' => $this->roomCategory->id,
            'floor_number' => 3,
            'status' => 'available',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
    }

    public function test_online_booking_creation_sets_expiration_and_locks_rooms(): void
    {
        // Act as authenticated customer
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $this->customer->update(['user_id' => $user->id]);

        $this->actingAs($user);
        $this->withoutExceptionHandling();

        // Send booking store request (online checkout)
        $response = $this->post(route('bookings.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '0987654321',
            'email' => 'jane.doe@example.com',
            'cccd' => '123456789012',
            'address' => 'Hanoi',
            'check_in_date' => '2026-08-01',
            'check_out_date' => '2026-08-02',
            'adult_count' => 2,
            'child_count' => 1,
            'room_category_id' => $this->roomCategory->id,
            'payment_type' => 'full_100',
        ]);

        // Expect redirect to VNPay
        $response->assertRedirect();
        $this->assertStringContainsString('vnp_Amount', $response->headers->get('Location'));

        // Recheck booking properties
        $booking = Booking::latest()->first();
        $this->assertNotNull($booking);
        $this->assertEquals('pending', $booking->status);
        $this->assertNotNull($booking->expires_at);
        $this->assertTrue($booking->expires_at->isAfter(now()));

        // Check if room is reserved
        $this->room->refresh();
        $this->assertEquals('reserved', $this->room->status);
    }

    public function test_expired_online_bookings_cancelled_by_scheduler(): void
    {
        // Create booking that is already expired
        $booking = Booking::create([
            'booking_code' => 'BK_EXP_001',
            'customer_id' => $this->customer->id,
            'room_category_id' => $this->roomCategory->id,
            'check_in_date' => '2026-08-01',
            'check_out_date' => '2026-08-02',
            'check_in_at' => '2026-08-01 14:00:00',
            'check_out_at' => '2026-08-02 12:00:00',
            'estimated_total' => 2000000,
            'booking_source' => 'user_online',
            'status' => 'pending',
            'expires_at' => now()->subMinutes(5),
        ]);

        // Link room to booking
        \App\Models\BookingRoom::create([
            'booking_id' => $booking->id,
            'room_id' => $this->room->id,
            'check_in_at' => $booking->check_in_at,
            'check_out_at' => $booking->check_out_at,
            'adult_count' => 2,
            'status' => 'reserved',
        ]);
        $this->room->update(['status' => 'reserved']);

        // Run schedule:run or trigger the scheduler manually
        // We can just call schedule run or execute the closure via artisan command, but since it is in console.php we can use Artisan schedule
        $this->artisan('schedule:run');

        // Verify booking status is now cancelled
        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
        $this->assertStringContainsString('Đã tự động hủy do hết hạn giữ phòng', $booking->note);

        // Verify room is released back to available
        $this->room->refresh();
        $this->assertEquals('available', $this->room->status);
    }

    public function test_new_payment_request_invalidates_previous_requests(): void
    {
        $booking = Booking::create([
            'booking_code' => 'BK_INV_001',
            'customer_id' => $this->customer->id,
            'room_category_id' => $this->roomCategory->id,
            'check_in_date' => '2026-08-01',
            'check_out_date' => '2026-08-02',
            'check_in_at' => '2026-08-01 14:00:00',
            'check_out_at' => '2026-08-02 12:00:00',
            'estimated_total' => 2000000,
            'status' => 'pending',
        ]);

        // Create first VNPay payment request
        $payment1 = BookingPayment::create([
            'booking_id' => $booking->id,
            'amount' => 2000000,
            'status' => 'pending',
            'provider' => 'vnpay',
            'txn_ref' => 'TXN_OLD_LINK',
            'payment_type' => 'full_100',
        ]);

        $this->actingAs($this->admin);

        // Create a new payment request via admin (which replaces the previous ones)
        $response = $this->post(route('admin.bookings.vnpay.create', $booking), [
            'payment_type' => 'full_100',
            'customer_email' => 'jane.doe@example.com',
        ]);

        // Assert redirect/success
        $response->assertRedirect();

        // Check if the old payment request has been marked as failed/REPLACED
        $payment1->refresh();
        $this->assertEquals('failed', $payment1->status);
        $this->assertEquals('REPLACED', $payment1->response_code);
    }

    public function test_recording_direct_payment_voids_pending_vnpay_requests(): void
    {
        $booking = Booking::create([
            'booking_code' => 'BK_VOID_001',
            'customer_id' => $this->customer->id,
            'room_category_id' => $this->roomCategory->id,
            'check_in_date' => '2026-08-01',
            'check_out_date' => '2026-08-02',
            'check_in_at' => '2026-08-01 14:00:00',
            'check_out_at' => '2026-08-02 12:00:00',
            'estimated_total' => 2000000,
            'status' => 'pending',
        ]);

        // Create pending VNPay payment request
        $vnpayPayment = BookingPayment::create([
            'booking_id' => $booking->id,
            'amount' => 2000000,
            'status' => 'pending',
            'provider' => 'vnpay',
            'txn_ref' => 'TXN_VOID_ME',
            'payment_type' => 'full_100',
        ]);

        $this->actingAs($this->admin);
        $this->withoutExceptionHandling();

        // Record cash payment
        $response = $this->post(route('admin.bookings.payments.store', $booking), [
            'payment_method' => 'cash',
            'payment_type' => 'full_100',
            'payment_note' => 'Thu tiền mặt tại quầy',
        ]);

        $response->assertRedirect();

        // Verify the cash payment record succeeded and booking is paid
        $booking->refresh();
        $this->assertEquals('paid', $booking->payment_status);

        // Verify that the VNPay request was cancelled
        $vnpayPayment->refresh();
        $this->assertEquals('failed', $vnpayPayment->status);
        $this->assertEquals('CANCELLED', $vnpayPayment->response_code);
    }

    public function test_late_vnpay_payments_sent_to_reconciliation(): void
    {
        $booking = Booking::create([
            'booking_code' => 'BK_LATE_001',
            'customer_id' => $this->customer->id,
            'room_category_id' => $this->roomCategory->id,
            'check_in_date' => '2026-08-01',
            'check_out_date' => '2026-08-02',
            'check_in_at' => '2026-08-01 14:00:00',
            'check_out_at' => '2026-08-02 12:00:00',
            'estimated_total' => 2000000,
            'status' => 'pending',
        ]);

        // Create a cancelled payment request (representing a replaced link or cash-overridden request)
        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'amount' => 2000000,
            'status' => 'failed',
            'provider' => 'vnpay',
            'txn_ref' => 'TXN_LATE_PAY',
            'payment_type' => 'full_100',
            'response_code' => 'CANCELLED',
        ]);

        // Mock VNPay successful callback (IPN) arriving late
        $response = $this->get(route('payment.vnpay.ipn', [
            'vnp_TxnRef' => 'TXN_LATE_PAY',
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_BankCode' => 'NCB',
            'vnp_TransactionNo' => '123456',
            'vnp_SecureHash' => 'dummy_hash', // In test mode VnpayService signature checking is bypassed or dummy matched depending on config/implementation. Wait! Let's check VnpayService signature check.
        ]));

        // Check if RspCode 02 is returned (Order already confirmed or transaction voided/replaced)
        $response->assertJson(['RspCode' => '02']);

        // Verify payment is still failed (unpaid on booking total) but marked for reconciliation
        $payment->refresh();
        $this->assertEquals('failed', $payment->status);
        $this->assertEquals('RECONCILIATION_NEEDED', $payment->response_code);

        // Verify booking log was created for reconciliation
        $this->assertDatabaseHas('booking_logs', [
            'booking_id' => $booking->id,
            'action' => 'vnpay_late_payment_reconciliation',
        ]);
    }

    public function test_vnpay_payment_arriving_exactly_during_cancellation_reopens_as_pending(): void
    {
        // Booking was cancelled/expired (released)
        $booking = Booking::create([
            'booking_code' => 'BK_EXPIRED_RACE',
            'customer_id' => $this->customer->id,
            'room_category_id' => $this->roomCategory->id,
            'check_in_date' => '2026-08-01',
            'check_out_date' => '2026-08-02',
            'check_in_at' => '2026-08-01 14:00:00',
            'check_out_at' => '2026-08-02 12:00:00',
            'estimated_total' => 2000000,
            'status' => 'cancelled',
        ]);

        // The VNPay request is still pending in DB
        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'amount' => 2000000,
            'status' => 'pending',
            'provider' => 'vnpay',
            'txn_ref' => 'TXN_RACE',
            'payment_type' => 'full_100',
        ]);

        // VNPay success callback arrives
        $response = $this->get(route('payment.vnpay.ipn', [
            'vnp_TxnRef' => 'TXN_RACE',
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_BankCode' => 'NCB',
            'vnp_TransactionNo' => '123456',
            'vnp_SecureHash' => 'dummy_hash',
        ]));

        $response->assertJson(['RspCode' => '00']);

        // Check that payment succeeded
        $payment->refresh();
        $this->assertEquals('success', $payment->status);

        // Check that booking was reopened to pending (instead of confirmed)
        $booking->refresh();
        $this->assertEquals('pending', $booking->status);
        $this->assertStringContainsString('CẢNH BÁO: Thanh toán VNPay thành công', $booking->note);

        // Check booking logs
        $this->assertDatabaseHas('booking_logs', [
            'booking_id' => $booking->id,
            'action' => 'vnpay_late_payment_after_cancelled',
        ]);
    }
}
