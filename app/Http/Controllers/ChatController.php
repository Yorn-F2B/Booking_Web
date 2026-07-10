<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageRealtimeSent;
use App\Models\ChatAttachment;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
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
        $conversation = $this->getCurrentConversation();

        if (!$conversation) {
            return response()->json([
                'conversation' => null,
                'messages' => [],
            ]);
        }

        $messages = ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->with(['sender', 'attachments'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'assigned_staff_name' => $conversation->assignedStaff?->name,
            ],
            'messages' => $messages->map(fn(ChatMessage $message) => $this->messagePayload($message)),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
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

        $storedPaths = [];

        try {
            [$conversation, $message] = DB::transaction(function () use ($request, &$storedPaths) {
                $conversation = $this->getOrCreateConversation($request);

                if (!$conversation->assigned_staff_id) {
                    $this->assignmentService->assign($conversation);
                    $conversation->refresh();
                }

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

            event(new ChatMessageRealtimeSent($message));

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
        $conversation = $this->getCurrentConversation();

        if ($conversation) {
            $conversation->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
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

    private function canAccessConversation(ChatConversation $conversation): bool
    {
        if (!Auth::check()) {
            return (int) session('chat_conversation_id') === (int) $conversation->id;
        }

        $user = Auth::user();

        if ($user->role === 'customer') {
            return (int) $conversation->customer_id === (int) $user->id;
        }

        return in_array($user->role, [
            'super_admin',
            'manager',
            'receptionist_lead',
            'receptionist',
        ], true);
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
