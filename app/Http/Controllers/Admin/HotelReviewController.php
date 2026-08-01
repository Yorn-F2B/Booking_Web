<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingLog;
use App\Models\HotelReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HotelReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $rating = $request->input('rating');
        $keyword = trim((string) $request->input('q'));

        $reviews = HotelReview::with(['booking.roomCategory', 'customer', 'user', 'replier'])
            ->when(in_array($status, ['pending', 'approved', 'hidden'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(in_array((int) $rating, [1, 2, 3, 4, 5], true), function ($query) use ($rating) {
                $query->where('rating', (int) $rating);
            })
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('comment', 'like', '%' . $keyword . '%')
                        ->orWhereHas('booking', function ($bookingQuery) use ($keyword) {
                            $bookingQuery->where('booking_code', 'like', '%' . $keyword . '%');
                        })
                        ->orWhereHas('customer', function ($customerQuery) use ($keyword) {
                            $customerQuery->where('first_name', 'like', '%' . $keyword . '%')
                                ->orWhere('last_name', 'like', '%' . $keyword . '%')
                                ->orWhere('phone', 'like', '%' . $keyword . '%')
                                ->orWhere('email', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => HotelReview::count(),
            'pending' => HotelReview::pending()->count(),
            'approved' => HotelReview::approved()->count(),
            'hidden' => HotelReview::hidden()->count(),
            'average' => round((float) HotelReview::approved()->avg('rating'), 1),
        ];

        return view('admin.pages.reviews.index', compact('reviews', 'stats', 'status', 'rating', 'keyword'));
    }

    public function show(HotelReview $hotelReview)
    {
        $hotelReview->load([
            'booking.roomCategory',
            'booking.bookingRooms.room',
            'customer',
            'user',
            'approver',
            'hider',
            'replier',
        ]);

        return view('admin.pages.reviews.show', [
            'review' => $hotelReview,
        ]);
    }

    public function reply(Request $request, HotelReview $hotelReview)
    {
        $data = $request->validate([
            'admin_reply' => 'required|string|min:3|max:2000',
        ], [
            'admin_reply.required' => 'Vui lòng nhập nội dung phản hồi.',
            'admin_reply.min' => 'Phản hồi nên có ít nhất 3 ký tự.',
            'admin_reply.max' => 'Phản hồi không vượt quá 2000 ký tự.',
        ]);

        DB::transaction(function () use ($hotelReview, $data) {
            $hotelReview->update([
                'admin_reply' => $data['admin_reply'],
                'replied_by' => Auth::id(),
                'replied_at' => now('Asia/Ho_Chi_Minh'),
            ]);

            BookingLog::create([
                'booking_id' => $hotelReview->booking_id,
                'user_id' => Auth::id(),
                'action' => 'review_replied',
                'description' => 'Admin phản hồi đánh giá khách sạn.',
            ]);
        });

        return back()->with('success', 'Đã lưu phản hồi đánh giá.');
    }

}
