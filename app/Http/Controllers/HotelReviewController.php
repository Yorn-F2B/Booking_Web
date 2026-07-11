<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Customer;
use App\Models\HotelReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        if ($booking->hotelReview()->exists()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', 'Mỗi đơn phòng chỉ được đánh giá một lần.');
        }

        $data = $this->validatedReviewData($request);

        DB::transaction(function () use ($data, $booking, $customer) {
            HotelReview::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'customer_id' => $customer->id,
                'room_category_id' => $booking->room_category_id,
                'rating' => $data['rating'],
                'cleanliness_rating' => $data['cleanliness_rating'],
                'service_rating' => $data['service_rating'],
                'location_rating' => $data['location_rating'],
                'value_rating' => $data['value_rating'],
                'title' => $data['title'] ?? null,
                'comment' => $data['comment'],
                'status' => HotelReview::STATUS_PENDING,
            ]);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'review_submitted',
                'description' => 'Khách gửi đánh giá khách sạn ' . $data['rating'] . '/5 sao. Chờ admin duyệt.',
            ]);
        });

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', 'Đã gửi đánh giá. Đánh giá sẽ hiển thị sau khi khách sạn duyệt.');
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

        DB::transaction(function () use ($data, $hotelReview) {
            $hotelReview->update([
                'rating' => $data['rating'],
                'cleanliness_rating' => $data['cleanliness_rating'],
                'service_rating' => $data['service_rating'],
                'location_rating' => $data['location_rating'],
                'value_rating' => $data['value_rating'],
                'title' => $data['title'] ?? null,
                'comment' => $data['comment'],
                'status' => HotelReview::STATUS_PENDING,
                'approved_by' => null,
                'approved_at' => null,
                'hidden_by' => null,
                'hidden_at' => null,
                'hidden_reason' => null,
            ]);

            BookingLog::create([
                'booking_id' => $hotelReview->booking_id,
                'user_id' => Auth::id(),
                'action' => 'review_updated',
                'description' => 'Khách chỉnh sửa đánh giá khách sạn. Trạng thái chuyển về chờ duyệt.',
            ]);
        });

        return redirect()
            ->route('bookings.show', $hotelReview->booking_id)
            ->with('success', 'Đã cập nhật đánh giá. Đánh giá sẽ được duyệt lại trước khi hiển thị.');
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
            'rating' => 'required|integer|min:1|max:5',
            'cleanliness_rating' => 'required|integer|min:1|max:5',
            'service_rating' => 'required|integer|min:1|max:5',
            'location_rating' => 'required|integer|min:1|max:5',
            'value_rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:150',
            'comment' => 'required|string|min:10|max:1500',
        ], [
            'rating.required' => 'Vui lòng chọn điểm đánh giá tổng thể.',
            'rating.min' => 'Điểm đánh giá phải từ 1 đến 5 sao.',
            'rating.max' => 'Điểm đánh giá phải từ 1 đến 5 sao.',
            '*.integer' => 'Điểm đánh giá không hợp lệ.',
            '*.min' => 'Điểm đánh giá phải từ 1 đến 5 sao.',
            '*.max' => 'Điểm đánh giá phải từ 1 đến 5 sao.',
            'comment.required' => 'Vui lòng nhập nội dung đánh giá.',
            'comment.min' => 'Nội dung đánh giá nên có ít nhất 10 ký tự.',
            'comment.max' => 'Nội dung đánh giá không vượt quá 1500 ký tự.',
            'title.max' => 'Tiêu đề không vượt quá 150 ký tự.',
        ]);
    }
}
