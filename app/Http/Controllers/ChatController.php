<?php

namespace App\Http\Controllers;

use App\Support\Realtime;
use App\Models\ChatAttachment;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Booking;
use App\Services\ChatAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(private ChatAssignmentService $assignmentService)
    {
    }

    public function messages(Request $request): JsonResponse
    {
        $this->guardPublicChatActor();
        $conversation = $this->getCurrentConversation();

        if (!$conversation) {
            return response()->json([
                'conversation' => null,
                'messages' => [],
                'has_more' => false,
            ]);
        }

        $data = $request->validate([
            'before_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $messagesQuery = ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->with(['sender', 'attachments']);

        if (!empty($data['before_id'])) {
            $messagesQuery->where('id', '<', (int) $data['before_id']);
        }

        $messages = $messagesQuery
            ->latest('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        $oldestId = $messages->min('id');
        $hasMore = $oldestId
            ? ChatMessage::query()
                ->where('conversation_id', $conversation->id)
                ->where('id', '<', $oldestId)
                ->exists()
            : false;

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'assigned_staff_name' => $conversation->assignedStaff?->name,
            ],
            'messages' => $messages->map(fn(ChatMessage $message) => $this->messagePayload($message)),
            'has_more' => $hasMore,
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $this->guardPublicChatActor();

        $request->validate([
            'message' => [
                'nullable',
                'string',
                'max:2000',
                'required_without_all:files,camera_image',
            ],

            'files' => [
                'nullable',
                'array',
                'max:5',
            ],

            'files.*' => [
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,txt,zip',
            ],

            'camera_image' => [
                'nullable',
                'image',
                'max:5120',
                'mimes:jpg,jpeg,png,webp',
            ],

            'guest_name' => ['nullable', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
            'booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
        ], [
            'message.required_without_all' => 'Vui lòng nhập nội dung, chọn file hoặc chụp ảnh.',
            'files.max' => 'Mỗi tin nhắn chỉ được gửi tối đa 5 file.',
            'files.*.max' => 'Mỗi file không được vượt quá 10 MB.',
            'files.*.mimes' => 'Định dạng file không được hỗ trợ.',
            'camera_image.max' => 'Ảnh chụp không được vượt quá 5 MB.',
            'camera_image.mimes' => 'Ảnh chụp không đúng định dạng.',
        ]);

        $bookingId = $this->resolveAccessibleBookingId($request->input('booking_id'));
        $request->merge(['booking_id' => $bookingId]);

        $storedPaths = [];

        try {
            [$conversation, $message] = DB::transaction(function () use ($request, &$storedPaths) {
                $conversation = $this->getOrCreateConversation($request);
                $conversation = ChatConversation::query()->lockForUpdate()->findOrFail($conversation->id);

                if ($conversation->status === 'closed') {
                    $conversation->update([
                        'status' => 'waiting',
                        'closed_at' => null,
                    ]);
                }

                // Mỗi lần khách nhắn lại đều xác nhận người đang phụ trách còn online.
                // Nếu người cũ đã nghỉ ca/offline, tự bàn giao sang người online ít tải nhất.
                $this->assignmentService->ensureAvailableAssignment($conversation);
                $conversation->refresh();

                $message = ChatMessage::create([
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'customer',
                    'sender_id' => Auth::check() && Auth::user()->role === 'customer'
                        ? Auth::id()
                        : null,
                    'message' => filled($request->message) ? trim($request->message) : null,
                    'is_read' => false,
                ]);

                $uploadedFiles = collect($request->file('files', []));

                if ($request->hasFile('camera_image')) {
                    $uploadedFiles->push($request->file('camera_image'));
                }

                foreach ($uploadedFiles as $uploadedFile) {
                    $extension = strtolower($uploadedFile->getClientOriginalExtension());
                    $storedName = Str::uuid() . ($extension ? ".{$extension}" : '');
                    $directory = 'chat-attachments/' . now()->format('Y/m/d');
                    $path = $uploadedFile->storeAs($directory, $storedName, 'local');
                    $storedPaths[] = $path;

                    ChatAttachment::create([
                        'message_id' => $message->id,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'mime_type' => $uploadedFile->getMimeType(),
                        'extension' => $extension,
                        'size' => $uploadedFile->getSize(),
                        'type' => str_starts_with((string) $uploadedFile->getMimeType(), 'image/')
                            ? 'image'
                            : 'file',
                    ]);
                }

                // Khách vừa gửi => chuyển sang Chờ phản hồi.
                $conversation->update([
                    'status' => 'waiting',
                    'closed_at' => null,
                    'last_message_at' => now(),
                ]);

                return [$conversation->refresh(), $message->load(['sender', 'attachments', 'conversation'])];
            });

            Realtime::chat($message);

            return response()->json([
                'success' => true,
                'conversation' => [
                    'id' => $conversation->id,
                    'status' => $conversation->status,
                ],
                'message' => $this->messagePayload($message),
            ]);
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            report($e);

            return response()->json([
                'message' => 'Không thể gửi tin nhắn. Vui lòng thử lại.',
            ], 500);
        }
    }

    public function close(): JsonResponse
    {
        $this->guardPublicChatActor();
        $conversation = $this->getCurrentConversation();

        if ($conversation) {
            DB::transaction(function () use ($conversation) {
                $locked = ChatConversation::query()->lockForUpdate()->findOrFail($conversation->id);
                abort_unless($this->canAccessConversation($locked), 403);
                $locked->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);
            });
        }

        return response()->json(['success' => true]);
    }

    public function download(ChatAttachment $attachment): StreamedResponse
    {
        $attachment->loadMissing('message.conversation');

        $conversation = $attachment->message?->conversation;

        abort_unless(
            $conversation && $this->canAccessConversation($conversation),
            403
        );

        $disk = Storage::disk($attachment->disk);

        abort_unless(
            $disk->exists($attachment->path),
            404
        );

        $headers = [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => ($attachment->type === 'image' ? 'inline' : 'attachment')
                . '; filename="' . addslashes($attachment->original_name) . '"',
        ];

        return response()->stream(function () use ($disk, $attachment) {
            $stream = $disk->readStream($attachment->path);

            if ($stream === false) {
                abort(404);
            }

            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $headers);
    }

    private function getCurrentConversation(): ?ChatConversation
    {
        if (Auth::check() && Auth::user()->role === 'customer') {
            return ChatConversation::query()
                ->with('assignedStaff')
                ->where('customer_id', Auth::id())
                ->latest('last_message_at')
                ->latest('id')
                ->first();
        }

        // Endpoint chat công khai chỉ dành cho khách hoặc khách vãng lai.
        // Không cho tài khoản nhân viên rơi xuống session guest cũ.
        if (Auth::check()) {
            return null;
        }

        $conversationId = session('chat_conversation_id');

        return $conversationId
            ? ChatConversation::with('assignedStaff')->find($conversationId)
            : null;
    }

    private function getOrCreateConversation(Request $request): ChatConversation
    {
        if ($conversation = $this->getCurrentConversation()) {
            return $conversation;
        }

        $conversation = ChatConversation::create([
            'customer_id' => Auth::check() && Auth::user()->role === 'customer'
                ? Auth::id()
                : null,
            'booking_id' => $request->booking_id,
            'guest_name' => $request->guest_name,
            'guest_email' => $request->guest_email,
            'guest_phone' => $request->guest_phone,
            'status' => 'waiting',
            'priority_score' => $this->calculatePriorityScore((string) $request->message, $request->booking_id),
            'last_message_at' => now(),
        ]);

        session(['chat_conversation_id' => $conversation->id]);

        return $conversation;
    }

    private function resolveAccessibleBookingId($bookingId): ?int
    {
        if (!$bookingId) {
            return null;
        }

        // Khách vãng lai chưa có bằng chứng sở hữu booking ngay trong luồng chat.
        // Không nhận booking_id từ client để tránh gắn hội thoại vào đơn của người khác.
        if (!Auth::check() || Auth::user()->role !== 'customer') {
            return null;
        }

        $booking = Booking::query()
            ->whereKey((int) $bookingId)
            ->whereHas('customer', fn ($query) => $query->where('user_id', Auth::id()))
            ->first();

        abort_unless($booking, 403, 'Booking không thuộc tài khoản đang đăng nhập.');

        return (int) $booking->id;
    }

    private function canAccessConversation(ChatConversation $conversation): bool
    {
        if (!Auth::check()) {
            return (int) session('chat_conversation_id') === (int) $conversation->id;
        }

        $user = Auth::user();

        if ($user->role === 'customer') {
            return (int) $conversation->customer_id === (int) $user->id;
        }

        if (in_array($user->role, ['super_admin', 'manager', 'receptionist_lead'], true)) {
            return true;
        }

        if ($user->role === 'receptionist') {
            return (int) $conversation->assigned_staff_id === (int) $user->id
                || ($conversation->assigned_staff_id === null && $conversation->status === 'waiting');
        }

        return false;
    }

    private function guardPublicChatActor(): void
    {
        if (Auth::check()) {
            abort_unless(Auth::user()->role === 'customer', 403, 'Tài khoản nhân viên phải sử dụng khu vực chat quản trị.');
        }
    }

    private function messagePayload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'sender_name' => $message->sender?->name,
            'message' => $message->message,
            'created_at' => $message->created_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
            'attachments' => $message->attachments->map(fn($file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'type' => $file->type,
                'download_url' => route('chat.attachments.download', $file),
            ])->values(),
        ];
    }

    private function calculatePriorityScore(string $message, $bookingId): int
    {
        $message = mb_strtolower($message);

        if (str_contains($message, 'thanh toán') || str_contains($message, 'cọc') || str_contains($message, 'vnpay')) {
            return 100;
        }

        if (str_contains($message, 'check in') || str_contains($message, 'nhận phòng') || str_contains($message, 'hôm nay')) {
            return 90;
        }

        return $bookingId ? 80 : 20;
    }
}
