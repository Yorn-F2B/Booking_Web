<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\CustomerRequest;
use App\Models\CustomerRequestAttachment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\Realtime;
use Illuminate\Validation\ValidationException;

class CustomerRequestController extends Controller
{
    public function create(Booking $booking)
    {
        $this->guardOnlineCustomer($booking);
        $this->guardCanRequestLateArrival($booking);

        $pendingRequest = $booking->customerRequests()->where('type', 'late_arrival')->where('status', 'pending')->latest('id')->first();

        return view('user.customer-requests.form', compact('booking', 'pendingRequest'));
    }


    public function show(Booking $booking)
    {
        $this->guardOnlineCustomer($booking);

        $customerRequest = $booking->customerRequests()
            ->where('type', 'late_arrival')
            ->with('attachments')
            ->latest('id')
            ->firstOrFail();

        return view('user.customer-requests.show', compact('booking', 'customerRequest'));
    }

    public function store(Booking $booking, Request $request)
    {
        $this->guardOnlineCustomer($booking);
        $this->guardCanRequestLateArrival($booking);

        $data = $this->validateLateArrivalRequest($booking, $request);
        $this->saveLateArrivalRequest($booking, $data, $request, 'customer_web');

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', 'Đã gửi yêu cầu đến sau giờ G. Khách sạn sẽ xem xét và phản hồi trên hệ thống.');
    }

    public function guestForm(Booking $booking)
    {
        $this->guardCanRequestLateArrival($booking);

        $pendingRequest = $booking->customerRequests()->where('type', 'late_arrival')->where('status', 'pending')->latest('id')->first();

        return view('guest-customer-requests.form', compact('booking', 'pendingRequest'));
    }

    public function guestStore(Booking $booking, Request $request)
    {
        $this->guardCanRequestLateArrival($booking);

        $data = $this->validateLateArrivalRequest($booking, $request);
        $this->saveLateArrivalRequest($booking, $data, $request, 'guest_email');

        return view('guest-customer-requests.done', compact('booking'));
    }


    public function attachment(Booking $booking, CustomerRequestAttachment $attachment)
    {
        $this->guardOnlineCustomer($booking);

        abort_unless((int) $attachment->customerRequest?->booking_id === (int) $booking->id, 404);

        $path = storage_path('app/public/' . ltrim($attachment->file_path, '/'));
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($attachment->original_name) . '"',
        ]);
    }

    public function guestAttachment(Booking $booking, CustomerRequestAttachment $attachment)
    {
        abort_unless((int) $attachment->customerRequest?->booking_id === (int) $booking->id, 404);

        $path = storage_path('app/public/' . ltrim($attachment->file_path, '/'));
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($attachment->original_name) . '"',
        ]);
    }

    private function validateLateArrivalRequest(Booking $booking, Request $request): array
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'expected_arrival_date' => ['required', 'date_format:Y-m-d'],
            'expected_arrival_time' => ['required', 'date_format:H:i'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'remove_attachment_ids' => ['nullable', 'array'],
            'remove_attachment_ids.*' => ['integer', 'min:1'],
        ], [
            'expected_arrival_date.required' => 'Vui lòng chọn ngày dự kiến đến.',
            'expected_arrival_date.date_format' => 'Ngày dự kiến đến không đúng định dạng.',
            'expected_arrival_time.required' => 'Vui lòng chọn giờ dự kiến đến.',
            'expected_arrival_time.date_format' => 'Giờ dự kiến đến phải theo định dạng 24 giờ HH:mm.',
        ]);

        $timezone = 'Asia/Ho_Chi_Minh';
        $arrival = Carbon::createFromFormat(
            'Y-m-d H:i',
            $data['expected_arrival_date'] . ' ' . $data['expected_arrival_time'],
            $timezone
        )->seconds(0);
        $cutoffLabel = $booking->lateArrivalCutoffTime();
        $cutoff = Carbon::createFromFormat('Y-m-d H:i', $booking->check_in_date . ' ' . substr($cutoffLabel, 0, 5), $timezone);
        $checkOut = $booking->check_out_at
            ? Carbon::parse($booking->check_out_at)->timezone($timezone)
            : Carbon::createFromFormat('Y-m-d H:i', $booking->check_out_date . ' ' . substr($booking->standardCheckOutTime(), 0, 5), $timezone);

        if ($arrival->lte($cutoff)) {
            throw ValidationException::withMessages([
                'expected_arrival_time' => 'Giờ dự kiến đến phải sau giờ G (' . substr($cutoffLabel, 0, 5) . ' ngày nhận phòng).',
            ]);
        }

        if ($arrival->gte($checkOut)) {
            throw ValidationException::withMessages([
                'expected_arrival_time' => 'Giờ dự kiến đến phải trước thời gian trả phòng của booking.',
            ]);
        }

        $data['expected_arrival_at'] = $arrival->format('Y-m-d H:i:s');
        unset($data['expected_arrival_date'], $data['expected_arrival_time']);

        return $data;
    }

    private function saveLateArrivalRequest(Booking $booking, array $data, Request $http, string $source): CustomerRequest
    {
        return DB::transaction(function () use ($booking, $data, $http, $source) {
            $item = $booking->customerRequests()
                ->where('type', 'late_arrival')
                ->where('status', 'pending')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($item) {
                $details = (array) ($item->details ?? []);
                $version = max(1, (int) ($details['version'] ?? 1)) + 1;
                $item->update([
                    'source' => $source,
                    'customer_name' => $data['customer_name'] ?? $booking->booked_customer_name,
                    'customer_email' => $data['customer_email'] ?? $booking->booked_customer_email,
                    'reason' => $data['reason'],
                    'requested_at' => now(),
                    'expected_arrival_at' => $data['expected_arrival_at'],
                    'details' => array_merge($details, [
                        'version' => $version,
                        'admin_acknowledged_version' => 0,
                        'last_update_summary' => 'Khách đã cập nhật lại giờ dự kiến đến và nội dung yêu cầu.',
                        'last_updated_at' => now('Asia/Ho_Chi_Minh')->toDateTimeString(),
                    ]),
                ]);
            } else {
                $item = CustomerRequest::create([
                    'booking_id' => $booking->id,
                    'type' => 'late_arrival',
                    'source' => $source,
                    'status' => 'pending',
                    'customer_name' => $data['customer_name'] ?? $booking->booked_customer_name,
                    'customer_email' => $data['customer_email'] ?? $booking->booked_customer_email,
                    'reason' => $data['reason'],
                    'requested_at' => now(),
                    'expected_arrival_at' => $data['expected_arrival_at'],
                    'details' => [
                        'version' => 1,
                        'admin_acknowledged_version' => 0,
                        'last_update_summary' => 'Khách gửi yêu cầu đến sau giờ G.',
                        'last_updated_at' => now('Asia/Ho_Chi_Minh')->toDateTimeString(),
                    ],
                ]);
            }

            $removeIds = collect($data['remove_attachment_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $attachmentsToRemove = $removeIds->isEmpty()
                ? collect()
                : $item->attachments()->whereIn('id', $removeIds)->get();
            $newFiles = $http->file('attachments', []);
            $remainingCount = $item->attachments()->count() - $attachmentsToRemove->count();

            if ($remainingCount + count($newFiles) > 5) {
                throw ValidationException::withMessages([
                    'attachments' => 'Tổng số ảnh/PDF minh chứng không được vượt quá 5 tệp.',
                ]);
            }

            $attachmentsToRemove->each(function ($attachment) {
                Storage::disk('public')->delete($attachment->file_path);
                $attachment->delete();
            });

            foreach ($newFiles as $file) {
                $path = $file->store('customer-requests/' . $item->id, 'public');
                $item->attachments()->create([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }

            Realtime::booking($booking, 'late_arrival_request_updated', false);
            return $item;
        });
    }

    private function guardOnlineCustomer(Booking $booking): void
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        abort_unless($customer && (int) $booking->customer_id === (int) $customer->id, 403);
    }

    private function guardCanRequestLateArrival(Booking $booking): void
    {
        abort_unless(in_array($booking->status, ['pending', 'confirmed'], true), 422, 'Booking hiện không thể gửi yêu cầu đến sau giờ G.');
    }
}
