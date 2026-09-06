<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Customer;
use App\Models\HotelReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ReviewContentFilter;

class HotelReviewController extends Controller
{
    public function create(Booking $booking)
    {
        $customer = $this->currentCustomer();
        $this->authorizeBookingOwner($booking, $customer);

        if (!$booking->canBeReviewed()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', 'Chỉ có thể đánh giá sau khi đơn phòng đã hoàn tất/trả phòng.');
        }

        if ($booking->hotelReview()->exists()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', 'Đơn phòng này đã có đánh giá. Bạn có thể chỉnh sửa đánh giá hiện có.');
        }

        $booking->load(['roomCategory', 'bookingRooms.room']);

        return view('user.reviews.create', compact('booking'));
    }

    public function store(Request $request, Booking $booking)
    {
        $customer = $this->currentCustomer();
        $this->authorizeBookingOwner($booking, $customer);

        if (!$booking->canBeReviewed()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', 'Chỉ có thể đánh giá sau khi đơn phòng đã hoàn tất/trả phòng.');
        }

        $data = $this->validatedReviewData($request);
        app(ReviewContentFilter::class)->assertClean($data['comment']);
        $averageRating = (int) round((
            $data['service_rating'] + $data['staff_rating'] + $data['room_quality_rating']
        ) / 3);

        $result = DB::transaction(function () use ($data, $booking, $customer, $averageRating) {
            // Khóa booking để hai POST gần như đồng thời không thể cùng tạo 2 review.
            Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $existing = HotelReview::query()->where('booking_id', $booking->id)->first();
            if ($existing) {
                $sameSubmission = (int) $existing->service_rating === (int) $data['service_rating']
                    && (int) $existing->staff_rating === (int) $data['staff_rating']
                    && (int) $existing->location_rating === (int) $data['room_quality_rating']
                    && trim((string) $existing->comment) === trim((string) $data['comment']);

                return $sameSubmission ? 'duplicate_same' : 'already_exists';
            }

            HotelReview::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'customer_id' => $customer->id,
                'room_category_id' => $booking->room_category_id,
                'rating' => $averageRating,
                'cleanliness_rating' => null,
                'service_rating' => $data['service_rating'],
                // Giữ location_rating làm cột tương thích lịch sử cho tiêu chí chất lượng phòng.
                'location_rating' => $data['room_quality_rating'],
                'staff_rating' => $data['staff_rating'],
                'comfort_rating' => null,
                'value_rating' => null,
                'title' => null,
                'comment' => $data['comment'],
                'status' => HotelReview::STATUS_APPROVED,
                'approved_at' => now('Asia/Ho_Chi_Minh'),
            ]);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'review_submitted',
                'description' => 'Khách gửi đánh giá khách sạn ' . $averageRating . '/5 sao (trung bình dịch vụ, nhân viên và chất lượng phòng). Đánh giá được hiển thị tự động sau khi vượt qua bộ lọc từ cấm.',
            ]);

            return 'created';
        });

        if ($result === 'already_exists') {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', 'Đơn phòng này đã có đánh giá khác. Hãy dùng nút Chỉnh sửa đánh giá.');
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', $result === 'duplicate_same'
                ? 'Đánh giá đã được lưu thành công.'
                : 'Đã đăng đánh giá. Nội dung đã vượt qua bộ lọc từ cấm và được hiển thị ngay.');
    }

    public function edit(HotelReview $hotelReview)
    {
        $customer = $this->currentCustomer();
        $this->authorizeReviewOwner($hotelReview, $customer);

        $hotelReview->load(['booking.roomCategory', 'booking.bookingRooms.room']);

        return view('user.reviews.edit', [
            'review' => $hotelReview,
            'booking' => $hotelReview->booking,
        ]);
    }

    public function update(Request $request, HotelReview $hotelReview)
    {
        $customer = $this->currentCustomer();
        $this->authorizeReviewOwner($hotelReview, $customer);

        $data = $this->validatedReviewData($request);
        app(ReviewContentFilter::class)->assertClean($data['comment']);
        $averageRating = (int) round((
            $data['service_rating'] + $data['staff_rating'] + $data['room_quality_rating']
        ) / 3);

        DB::transaction(function () use ($data, $hotelReview, $averageRating) {
            $hotelReview->update([
                'rating' => $averageRating,
                'cleanliness_rating' => null,
                'service_rating' => $data['service_rating'],
                // Giữ location_rating làm cột tương thích lịch sử cho tiêu chí chất lượng phòng.
                'location_rating' => $data['room_quality_rating'],
                'staff_rating' => $data['staff_rating'],
                'comfort_rating' => null,
                'value_rating' => null,
                'title' => null,
                'comment' => $data['comment'],
                'status' => HotelReview::STATUS_APPROVED,
                'approved_by' => null,
                'approved_at' => now('Asia/Ho_Chi_Minh'),
                'hidden_by' => null,
                'hidden_at' => null,
                'hidden_reason' => null,
            ]);

            BookingLog::create([
                'booking_id' => $hotelReview->booking_id,
                'user_id' => Auth::id(),
                'action' => 'review_updated',
                'description' => 'Khách chỉnh sửa đánh giá khách sạn. Nội dung được hiển thị lại tự động sau khi vượt qua bộ lọc từ cấm.',
            ]);
        });

        return redirect()
            ->route('bookings.show', $hotelReview->booking_id)
            ->with('success', 'Đã cập nhật đánh giá và hiển thị ngay.');
    }

    public function destroy(HotelReview $hotelReview)
    {
        $customer = $this->currentCustomer();
        $this->authorizeReviewOwner($hotelReview, $customer);

        DB::transaction(function () use ($hotelReview) {
            BookingLog::create([
                'booking_id' => $hotelReview->booking_id,
                'user_id' => Auth::id(),
                'action' => 'review_deleted',
                'description' => 'Khách xóa đánh giá khách sạn.',
            ]);

            $hotelReview->delete();
        });

        return redirect()
            ->route('bookings.show', $hotelReview->booking_id)
            ->with('success', 'Đã xóa đánh giá.');
    }

    private function currentCustomer(): Customer
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if (!$customer) {
            abort(403, 'Tài khoản này chưa có hồ sơ khách hàng.');
        }

        return $customer;
    }

    private function authorizeBookingOwner(Booking $booking, Customer $customer): void
    {
        if ((int) $booking->customer_id !== (int) $customer->id) {
            abort(403);
        }
    }

    private function authorizeReviewOwner(HotelReview $hotelReview, Customer $customer): void
    {
        if ((int) $hotelReview->customer_id !== (int) $customer->id) {
            abort(403);
        }
    }

    private function validatedReviewData(Request $request): array
    {
        return $request->validate([
            'room_quality_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'staff_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'service_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:10', 'max:1500'],
        ], [
            'room_quality_rating.required' => 'Vui lòng đánh giá chất lượng phòng.',
            'staff_rating.required' => 'Vui lòng đánh giá nhân viên.',
            'service_rating.required' => 'Vui lòng đánh giá dịch vụ.',
            'room_quality_rating.integer' => 'Điểm chất lượng phòng không hợp lệ.',
            'staff_rating.integer' => 'Điểm nhân viên không hợp lệ.',
            'service_rating.integer' => 'Điểm dịch vụ không hợp lệ.',
            'room_quality_rating.min' => 'Điểm chất lượng phòng phải từ 1 đến 5 sao.',
            'room_quality_rating.max' => 'Điểm chất lượng phòng phải từ 1 đến 5 sao.',
            'staff_rating.min' => 'Điểm nhân viên phải từ 1 đến 5 sao.',
            'staff_rating.max' => 'Điểm nhân viên phải từ 1 đến 5 sao.',
            'service_rating.min' => 'Điểm dịch vụ phải từ 1 đến 5 sao.',
            'service_rating.max' => 'Điểm dịch vụ phải từ 1 đến 5 sao.',
            'comment.required' => 'Vui lòng nhập nội dung đánh giá.',
            'comment.min' => 'Nội dung đánh giá phải có ít nhất 10 ký tự.',
            'comment.max' => 'Nội dung đánh giá không vượt quá 1500 ký tự.',
        ]);
    }
}
