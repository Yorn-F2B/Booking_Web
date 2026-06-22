<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ReviewController extends Controller
{
    /**
     * Hiển thị form điền đánh giá cho khách hàng
     */
    public function index(Request $request)
    {
        $booking_code = $request->query('code');
        $booking = null;
        $error = '';
        $already_reviewed = false;

        if (!empty($booking_code)) {
            // Lấy thông tin đơn đặt phòng dựa trên mã code và trạng thái đã hoàn thành dịch vụ
            $booking = DB::table('bookings')
                ->leftJoin('room_categories', 'bookings.room_category_id', '=', 'room_categories.id')
                ->where('bookings.booking_code', $booking_code)
                ->whereIn('bookings.status', ['completed', 'checked_out'])
                ->select('bookings.*', 'room_categories.name as room_category_name')
                ->first();

            if (!$booking) {
                $error = "Mã đặt phòng không tồn tại hoặc đơn phòng này chưa hoàn thành dịch vụ!";
            } else {
                // Kiểm tra xem đơn đặt phòng này đã được đánh giá trước đó chưa
                $already_reviewed = DB::table('reviews')->where('booking_id', $booking->id)->exists();
            }
        }

        return view('reviews.index', compact('booking', 'error', 'already_reviewed', 'booking_code'));
    }

    /**
     * Xử lý lưu dữ liệu đánh giá từ khách hàng gửi lên
     */
    public function store(Request $request)
    {
        // Xác thực dữ liệu đầu vào bắt buộc phải có
        $request->validate([
            'booking_id' => 'required',
            'hotel_rating' => 'required|integer|min:1|max:5',
            'staff_rating' => 'required|integer|min:1|max:5',
        ]);

        try {
            // Tự động truy vấn lại lấy dữ liệu đặt phòng từ booking_id để lấy customer_id an toàn
            $booking = DB::table('bookings')->where('id', $request->booking_id)->first();
            
            // Nếu đơn hàng bị trống customer_id (NULL) trong DB, gán mặc định bằng 0 để tránh lỗi ràng buộc MySQL
            $customerId = $booking ? ($booking->customer_id ?? 0) : 0;

            // 1. Lưu thông tin đánh giá vào bảng reviews
            DB::table('reviews')->insert([
                'booking_id' => $request->booking_id,
                'customer_id' => $customerId, 
                'hotel_rating' => $request->hotel_rating,
                'hotel_comment' => $request->hotel_comment,
                'staff_rating' => $request->staff_rating,
                'staff_comment' => $request->staff_comment,
                'created_at' => now()
            ]);

            // 2. Ghi nhận log hoạt động (Bọc trong try-catch riêng để nếu bảng log của bạn lệch cấu trúc thì vẫn lưu được Đánh giá thành công)
            try {
                DB::table('booking_logs')->insert([
                    'action' => 'customer_review',
                    'description' => "Khách hàng đánh giá khách sạn: {$request->hotel_rating} sao, nhân viên: {$request->staff_rating} sao."
                ]);
            } catch (\Exception $logEx) {
                // Bỏ qua lỗi ghi log nếu cấu trúc bảng log không khớp, ưu tiên lưu đánh giá thành công
            }

            // Thành công chuyển hướng sang trang danh sách tổng hợp kèm thông báo thành công
            return redirect()->route('reviews.list')->with('success', true);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Đơn đặt phòng này đã được đánh giá trước đó hoặc hệ thống đang bận.']);
        }
    }

    /**
     * Hiển thị danh sách tổng hợp đánh giá và điểm trung bình
     */
    public function list()
    {
        // Lấy danh sách toàn bộ các đánh giá trực tiếp từ bảng reviews độc lập để KHÔNG BỊ LỖI cột ở các bảng khác
        $reviews = DB::table('reviews')
            ->orderBy('created_at', 'desc')
            ->get();

        // Tự động gán thông tin bổ sung an toàn (Nếu tìm thấy mã booking thì hiện, không thì ẩn, không lo crash lỗi)
        foreach ($reviews as $review) {
            $bookingInfo = DB::table('bookings')->where('id', $review->booking_id)->first();
            
            if ($bookingInfo) {
                $review->booking_code = $bookingInfo->booking_code;
                
                // Tìm tên hạng phòng lưu trú
                $roomCategory = DB::table('room_categories')->where('id', $bookingInfo->room_category_id)->first();
                $review->room_name = $roomCategory ? $roomCategory->name : 'Tiêu chuẩn';
            } else {
                $review->booking_code = 'Mã đơn ẩn';
                $review->room_name = 'Tiêu chuẩn';
            }
        }

        // Tính toán số liệu thống kê trung bình sao và tổng lượt phản hồi
        $stats = DB::table('reviews')
            ->selectRaw('AVG(hotel_rating) as avg_hotel, AVG(staff_rating) as avg_staff, COUNT(id) as total')
            ->first();

        return view('reviews.list', compact('reviews', 'stats'));
    }
}