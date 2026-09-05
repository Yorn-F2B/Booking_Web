<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserSettingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\RoomController;
use App\Models\RoomCategory;
use App\Http\Controllers\BookingController;
use Carbon\Carbon;
use App\Http\Controllers\Payment\VnpayController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\HotelReviewController;
use App\Http\Controllers\CustomerRequestController;
use App\Models\HotelReview;
use App\Models\Promotion;
use App\Http\Controllers\RoomIssueRequestController;
use App\Http\Controllers\GuestBookingLookupController;
use App\Http\Controllers\CitizenIdScanController;
use App\Services\HotelPolicyService;
use App\Http\Controllers\UserNotificationController;

/*
|--------------------------------------------------------------------------
| HOME PAGE
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $featuredRoomCategories = RoomCategory::with(['images', 'amenities'])
        ->where('status', 'active')
        ->latest()
        ->take(6)
        ->get();

    $now = Carbon::now('Asia/Ho_Chi_Minh');
    $standardCheckInTime = (string) app(HotelPolicyService::class)->get('stay.standard_check_in_time', '14:00');
    $todayCheckInDeadline = Carbon::parse($now->toDateString() . ' ' . $standardCheckInTime, 'Asia/Ho_Chi_Minh');

    $onlineBookingClosedToday = $now->greaterThanOrEqualTo($todayCheckInDeadline);

    $minOnlineCheckInDate = $onlineBookingClosedToday
        ? $now->copy()->addDay()->toDateString()
        : $now->toDateString();

    $minOnlineCheckOutDate = Carbon::parse($minOnlineCheckInDate, 'Asia/Ho_Chi_Minh')
        ->addDay()
        ->toDateString();

    $maxOnlineGuests = max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_guests', 60));
    $maxAdultCapacity = $maxOnlineGuests;
    $maxChildCapacity = $maxOnlineGuests;

    $approvedHotelReviews = HotelReview::approved()
        ->with(['customer', 'booking.roomCategory', 'replier'])
        ->latest('approved_at')
        ->take(6)
        ->get();

    $hotelReviewStats = [
        'count' => HotelReview::approved()->count(),
        'average' => round((float) HotelReview::approved()->avg('rating'), 1),
    ];

    $publicPromotions = Promotion::query()
        ->where('status', 'active')
        ->where('is_public', true)
        ->where('user_can_apply', true)
        // Không đưa mã dữ liệu thử nghiệm ra trang công khai khi demo/vận hành.
        ->where('code', 'not like', 'DEMO%')
        ->where(function ($query) use ($now) {
            $query->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
        })
        ->where(function ($query) use ($now) {
            $query->whereNull('valid_to')->orWhere('valid_to', '>=', $now);
        })
        ->where(function ($query) {
            $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
        })
        ->latest()
        ->take(3)
        ->get();

    return view('user.pages.home', compact(
        'featuredRoomCategories',
        'minOnlineCheckInDate',
        'minOnlineCheckOutDate',
        'onlineBookingClosedToday',
        'maxAdultCapacity',
        'maxChildCapacity',
        'approvedHotelReviews',
        'hotelReviewStats',
        'publicPromotions',
        'standardCheckInTime'
    ));
})->name('home');

Route::post('/cccd/scan', CitizenIdScanController::class)
    ->middleware('throttle:20,1')
    ->name('cccd.scan');

/*
|--------------------------------------------------------------------------
| AUTH DASHBOARD COMPATIBILITY
|--------------------------------------------------------------------------
| Breeze controllers and the default navigation still call route('dashboard').
| Keep one compatibility route and redirect each role to its real landing page.
*/
Route::middleware('auth')->get('/dashboard', function () {
    $role = auth()->user()?->role;

    return match ($role) {
        'super_admin' => redirect()->route('admin.dashboard'),
        'manager' => redirect()->route('admin.bookings.index'),
        'receptionist_lead', 'receptionist' => redirect()->route('admin.bookings.index'),
        'housekeeping_supervisor', 'housekeeping' => redirect()->route('admin.housekeeping.index'),
        default => redirect()->route('home'),
    };
})->name('dashboard');

/*
|--------------------------------------------------------------------------
| USER PAGES
|--------------------------------------------------------------------------
*/

Route::get('/about', function () {
    return view('user.pages.about');
});

Route::get('/rooms', [RoomController::class, 'index'])->name('rooms');
Route::get('/rooms/{roomCategory}/availability', [RoomController::class, 'availability'])
    ->middleware('throttle:60,1')
    ->name('rooms.availability');
Route::get('/rooms/{roomCategory}', [RoomController::class, 'show'])->name('rooms.show');

Route::middleware(['auth', 'role:customer'])->get('/booking-history', [BookingController::class, 'history'])
    ->name('bookings.history');

Route::get('/contact', function () {
    return view('user.pages.contact');
});

/*
|--------------------------------------------------------------------------
| GUEST BOOKING LOOKUP
|--------------------------------------------------------------------------
*/

Route::get('/tra-cuu-booking', [GuestBookingLookupController::class, 'index'])
    ->name('guest-bookings.index');
Route::post('/tra-cuu-booking/gui-otp', [GuestBookingLookupController::class, 'sendOtp'])
    ->middleware('throttle:30,10')
    ->name('guest-bookings.send-otp');
Route::get('/tra-cuu-booking/xac-thuc', [GuestBookingLookupController::class, 'verifyForm'])
    ->name('guest-bookings.verify-form');
Route::post('/tra-cuu-booking/xac-thuc', [GuestBookingLookupController::class, 'verifyOtp'])
    ->middleware('throttle:30,10')
    ->name('guest-bookings.verify');
Route::get('/tra-cuu-booking/chi-tiet/{token}', [GuestBookingLookupController::class, 'show'])
    ->name('guest-bookings.show');
Route::post('/tra-cuu-booking/chi-tiet/{token}/xac-nhan-phong-du-phong', [GuestBookingLookupController::class, 'respondToRoomSelectionFallback'])
    ->middleware('throttle:10,10')
    ->name('guest-bookings.room-selection-fallback');
Route::post('/tra-cuu-booking/chi-tiet/{token}/huy', [GuestBookingLookupController::class, 'cancel'])
    ->middleware('throttle:10,10')
    ->name('guest-bookings.cancel');
Route::get('/tra-cuu-booking/da-huy', [GuestBookingLookupController::class, 'cancelled'])
    ->name('guest-bookings.cancelled');

/*
|--------------------------------------------------------------------------
| SIGNED ROOM ISSUE FORM FROM RECEPTION
|--------------------------------------------------------------------------
*/

Route::get('/bao-su-co-phong/{booking}', [RoomIssueRequestController::class, 'emailForm'])
    ->middleware(['signed', 'throttle:30,10'])
    ->name('guest-room-issues.form');
Route::post('/bao-su-co-phong/{booking}', [RoomIssueRequestController::class, 'storeFromEmail'])
    ->middleware(['signed', 'throttle:30,10'])
    ->name('guest-room-issues.store');


/* CUSTOMER REQUEST FORMS */
Route::get('/yeu-cau-booking/{booking}', [CustomerRequestController::class, 'guestForm'])
    ->middleware(['signed', 'throttle:30,10'])->name('guest-customer-requests.form');
Route::post('/yeu-cau-booking/{booking}', [CustomerRequestController::class, 'guestStore'])
    ->middleware(['signed', 'throttle:30,10'])->name('guest-customer-requests.store');
Route::get('/yeu-cau-booking/{booking}/tep/{attachment}', [CustomerRequestController::class, 'guestAttachment'])
    ->middleware(['signed', 'throttle:60,10'])->name('guest-customer-requests.attachment');

/*
|--------------------------------------------------------------------------
| CHAT ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/chat/messages', [ChatController::class, 'messages'])
    ->middleware('throttle:60,1')
    ->name('chat.messages');

Route::post('/chat/send', [ChatController::class, 'send'])
    ->middleware('throttle:20,1')
    ->name('chat.send');

Route::post('/chat/close', [ChatController::class, 'close'])
    ->middleware('throttle:30,1')
    ->name('chat.close');

Route::get('/chat/attachments/{attachment}/download', [ChatController::class, 'download'])
    ->middleware('throttle:60,1')
    ->name('chat.attachments.download');

/*
|--------------------------------------------------------------------------
| USER SETTINGS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/user-settings', [UserSettingController::class, 'index'])
        ->name('user.settings');

    Route::post('/user-settings', [UserSettingController::class, 'update'])
        ->name('user.settings.update');

    Route::post('/user-password', [UserSettingController::class, 'updatePassword'])
        ->name('user.password.update');
});

/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:customer'])->group(function () {
    // Đã bỏ nghiệp vụ tiền bảo lưu khi hủy phòng.

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::get('/bookings/current', [BookingController::class, 'current'])
        ->name('bookings.current');

    Route::get('/notifications', [UserNotificationController::class, 'index'])
        ->name('notifications.index');
    Route::get('/notifications/{notification}/open', [UserNotificationController::class, 'open'])
        ->name('notifications.open');
    Route::post('/notifications/read-all', [UserNotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');


    Route::get('/bookings/{booking}/customer-request', [CustomerRequestController::class, 'create'])
        ->name('bookings.customer-requests.create');
    Route::get('/bookings/{booking}/customer-request/detail', [CustomerRequestController::class, 'show'])
        ->name('bookings.customer-requests.show');
    Route::post('/bookings/{booking}/customer-request', [CustomerRequestController::class, 'store'])
        ->name('bookings.customer-requests.store');
    Route::get('/bookings/{booking}/customer-request/attachments/{attachment}', [CustomerRequestController::class, 'attachment'])
        ->name('bookings.customer-requests.attachment');

    Route::post('/bookings/{booking}/room-selection-fallback', [BookingController::class, 'respondToRoomSelectionFallback'])
        ->name('bookings.room-selection-fallback');

    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])
        ->name('bookings.cancel');

    Route::post('/bookings/{booking}/room-issue-requests', [RoomIssueRequestController::class, 'store'])
        ->name('bookings.room-issues.store');

    Route::post('/booking-history/{booking}/services', [BookingController::class, 'storeCustomerService'])
        ->name('bookings.services.store');


    Route::get('/booking-history/{booking}/edit-before-payment', [BookingController::class, 'editBeforePayment'])
        ->name('bookings.edit-before-payment');
    Route::patch('/booking-history/{booking}/edit-before-payment', [BookingController::class, 'updateBeforePayment'])
        ->name('bookings.update-before-payment');

    Route::get('/booking-history/{booking}/review', [HotelReviewController::class, 'create'])
        ->name('bookings.reviews.create');

    Route::post('/booking-history/{booking}/review', [HotelReviewController::class, 'store'])
        ->name('bookings.reviews.store');

    Route::get('/reviews/{hotelReview}/edit', [HotelReviewController::class, 'edit'])
        ->name('reviews.edit');

    Route::put('/reviews/{hotelReview}', [HotelReviewController::class, 'update'])
        ->name('reviews.update');

    Route::delete('/reviews/{hotelReview}', [HotelReviewController::class, 'destroy'])
        ->name('reviews.destroy');

    Route::get(
        '/booking-history/{booking}',
        [BookingController::class, 'show']
    )->name('bookings.show');
});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| PAYMENT ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/payment/vnpay/return', [VnpayController::class, 'return'])
    ->name('payment.vnpay.return');

Route::get('/payment/vnpay/ipn', [VnpayController::class, 'ipn'])
    ->name('payment.vnpay.ipn');

Route::get('/payment/vnpay/admin-request/{payment}', [VnpayController::class, 'payRequest'])
    ->name('payment.vnpay.admin-request');

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::post('/payment/vnpay/{booking}', [VnpayController::class, 'create'])
        ->name('payment.vnpay.create');
});

Route::get('/bookings/confirm', [BookingController::class, 'confirm'])
    ->name('bookings.confirm');

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::post('/bookings/eligible-promotions', [BookingController::class, 'eligiblePromotions'])
        ->name('bookings.eligible-promotions');
    Route::post('/bookings', [BookingController::class, 'store'])
        ->name('bookings.store');
});
