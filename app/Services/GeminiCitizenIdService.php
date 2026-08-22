<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiCitizenIdService
{
    public function scan(UploadedFile $image, array $requiredFields = []): array
    {
        $apiKey = trim((string) config('services.gemini.api_key'));
        $model = trim((string) config('services.gemini.model', 'gemini-3.5-flash-lite'));
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        if ($apiKey === '') {
            throw new RuntimeException('Chưa cấu hình GEMINI_API_KEY trong file .env.');
        }

        $bytes = file_get_contents($image->getRealPath());
        if ($bytes === false) {
            throw new RuntimeException('Không thể đọc file ảnh CCCD đã tải lên.');
        }

        $mimeType = $image->getMimeType() ?: 'image/jpeg';
        $requiredFields = $this->normalizeRequiredFields($requiredFields);

        $raw = $this->requestStructuredJson(
            apiKey: $apiKey,
            model: $model,
            baseUrl: $baseUrl,
            bytes: $bytes,
            mimeType: $mimeType,
            prompt: $this->fullDocumentPrompt(),
            schema: $this->fullDocumentSchema(),
        );

        $result = $this->normalizeScanResult($raw);

        if ($this->shouldRunFocusedFrontRetry($result, $requiredFields)) {
            try {
                $frontFocusedRaw = $this->requestStructuredJson(
                    apiKey: $apiKey,
                    model: $model,
                    baseUrl: $baseUrl,
                    bytes: $bytes,
                    mimeType: $mimeType,
                    prompt: $this->frontFocusedPrompt($result),
                    schema: $this->frontFocusedSchema(),
                );

                $result = $this->mergeMissingFields($result, $this->normalizeScanResult($frontFocusedRaw));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if ($this->shouldRunBirthdayRetry($result, $requiredFields)) {
            try {
                $birthdayRaw = $this->requestStructuredJson(
                    apiKey: $apiKey,
                    model: $model,
                    baseUrl: $baseUrl,
                    bytes: $bytes,
                    mimeType: $mimeType,
                    prompt: $this->birthdayFocusedPrompt($result),
                    schema: $this->birthdayFocusedSchema(),
                );

                $birthdayData = $this->normalizeScanResult($birthdayRaw);
                if ($birthdayData['birthday'] !== '') {
                    $result['birthday'] = $birthdayData['birthday'];
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $result;
    }

    private function requestStructuredJson(
        string $apiKey,
        string $model,
        string $baseUrl,
        string $bytes,
        string $mimeType,
        string $prompt,
        array $schema,
    ): array {
        // Google AI Studio hiện tạo authorization key dạng AQ.
        // Key AQ. đi theo Interactions API mới; key AIza cũ vẫn giữ nguyên
        // luồng GenerateContent đã được project kiểm thử ổn định trước đây.
        if (str_starts_with($apiKey, 'AQ.')) {
            return $this->requestStructuredJsonViaInteractions(
                apiKey: $apiKey,
                model: $model,
                baseUrl: $baseUrl,
                bytes: $bytes,
                mimeType: $mimeType,
                prompt: $prompt,
                schema: $schema,
            );
        }

        return $this->requestStructuredJsonViaGenerateContent(
            apiKey: $apiKey,
            model: $model,
            baseUrl: $baseUrl,
            bytes: $bytes,
            mimeType: $mimeType,
            prompt: $prompt,
            schema: $schema,
        );
    }

    private function requestStructuredJsonViaInteractions(
        string $apiKey,
        string $model,
        string $baseUrl,
        string $bytes,
        string $mimeType,
        string $prompt,
        array $schema,
    ): array {
        $response = Http::asJson()
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
            ])
            ->timeout(45)
            ->retry(1, 400, throw: false)
            ->post("{$baseUrl}/interactions", [
                'model' => $model,
                'input' => [
                    ['type' => 'text', 'text' => $prompt],
                    [
                        'type' => 'image',
                        'data' => base64_encode($bytes),
                        'mime_type' => $mimeType,
                    ],
                ],
                'response_format' => [[
                    'type' => 'text',
                    'mime_type' => 'application/json',
                    'schema' => $this->normalizeJsonSchemaForInteractions($schema),
                ]],
            ]);

        if ($response->failed()) {
            $this->throwGeminiHttpError($response->status(), (string) data_get($response->json(), 'error.message', ''));
        }

        $text = $this->interactionOutputText($response->json());
        if ($text === '') {
            $status = (string) data_get($response->json(), 'status', '');
            throw new RuntimeException($status !== ''
                ? 'Gemini chưa trả dữ liệu CCCD. Trạng thái: ' . $status . '.'
                : 'Gemini chưa trả dữ liệu nhận diện CCCD.');
        }

        return $this->decodeStructuredJsonText($text);
    }

    private function requestStructuredJsonViaGenerateContent(
        string $apiKey,
        string $model,
        string $baseUrl,
        string $bytes,
        string $mimeType,
        string $prompt,
        array $schema,
    ): array {
        $response = Http::asJson()
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
            ])
            ->timeout(45)
            ->retry(1, 400, throw: false)
            ->post("{$baseUrl}/models/{$model}:generateContent", [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => base64_encode($bytes),
                            ],
                        ],
                    ],
                ]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $schema,
                ],
            ]);

        if ($response->failed()) {
            $this->throwGeminiHttpError($response->status(), (string) data_get($response->json(), 'error.message', ''));
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
        if (!is_string($text) || trim($text) === '') {
            $finishReason = (string) data_get($response->json(), 'candidates.0.finishReason', '');
            throw new RuntimeException($finishReason !== ''
                ? 'Gemini chưa trả dữ liệu CCCD. Trạng thái: ' . $finishReason . '.'
                : 'Gemini chưa trả dữ liệu nhận diện CCCD.');
        }

        return $this->decodeStructuredJsonText($text);
    }

    private function throwGeminiHttpError(int $status, string $message): never
    {
        if ($status === 401) {
            throw new RuntimeException(
                'Gemini không xác thực được API key hiện tại (HTTP 401). '
                . 'Kiểm tra GEMINI_API_KEY trong .env hoặc trạng thái key trên Google AI Studio.'
            );
        }

        throw new RuntimeException($message !== ''
            ? 'Gemini API lỗi: ' . $message
            : 'Gemini API không phản hồi hợp lệ (HTTP ' . $status . ').');
    }

    private function interactionOutputText(array $payload): string
    {
        $parts = [];

        foreach ((array) ($payload['steps'] ?? []) as $step) {
            if (($step['type'] ?? null) !== 'model_output') {
                continue;
            }

            foreach ((array) ($step['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'text' && is_string($content['text'] ?? null)) {
                    $parts[] = $content['text'];
                }
            }
        }

        return trim(implode("\n", $parts));
    }

    private function decodeStructuredJsonText(string $text): array
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text) ?? $text;
            $text = trim($text);
        }

        $raw = json_decode($text, true);
        if (!is_array($raw)) {
            throw new RuntimeException('Gemini trả dữ liệu không đúng định dạng JSON.');
        }

        return $raw;
    }

    private function normalizeJsonSchemaForInteractions(array $schema): array
    {
        $normalized = [];

        foreach ($schema as $key => $value) {
            if ($key === 'type' && is_string($value)) {
                $normalized[$key] = strtolower($value);
                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalizeJsonSchemaForInteractions($value);
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private function normalizeScanResult(array $raw): array
    {
        $cccd = preg_replace('/\D+/', '', (string) ($raw['cccd'] ?? $raw['id_number'] ?? '')) ?: '';
        if ($cccd !== '' && !in_array(strlen($cccd), [9, 12], true)) {
            $cccd = '';
        }

        return [
            'cccd' => $cccd,
            'full_name' => $this->normalizePersonName($raw['full_name'] ?? $raw['name'] ?? null),
            'birthday' => $this->normalizeDate($raw['birthday'] ?? $raw['date_of_birth'] ?? null),
            'gender' => $this->normalizeGender($raw['gender'] ?? null),
            'nationality' => $this->clean($raw['nationality'] ?? null),
            'address' => $this->clean($raw['address'] ?? $raw['place_of_residence'] ?? null),
            'place_of_origin' => $this->clean($raw['place_of_origin'] ?? null),
            'expiry_date' => $this->normalizeDate($raw['expiry_date'] ?? $raw['date_of_expiry'] ?? null),
            'issue_date' => $this->normalizeDate($raw['issue_date'] ?? $raw['date_of_issue'] ?? null),
            'identifying_characteristics' => $this->clean($raw['identifying_characteristics'] ?? null),
            'document_side' => $this->normalizeDocumentSide($raw['document_side'] ?? null),
            'document_type' => $this->clean($raw['document_type'] ?? null),
            'confidence_note' => $this->clean($raw['confidence_note'] ?? null),
        ];
    }

    private function shouldRunFocusedFrontRetry(array $result, array $requiredFields): bool
    {
        if ($result['document_side'] === 'back') {
            return false;
        }

        $fieldsToCheck = $requiredFields !== []
            ? $requiredFields
            : ['full_name', 'birthday', 'gender', 'nationality', 'place_of_origin', 'address'];

        foreach ($fieldsToCheck as $field) {
            if (($result[$field] ?? '') === '') {
                return true;
            }
        }

        return false;
    }

    private function shouldRunBirthdayRetry(array $result, array $requiredFields): bool
    {
        $birthdayRequired = $requiredFields === [] || in_array('birthday', $requiredFields, true);
        return $birthdayRequired && $result['document_side'] !== 'back' && ($result['birthday'] ?? '') === '';
    }

    private function normalizeRequiredFields(array $fields): array
    {
        $allowed = [
            'cccd', 'full_name', 'birthday', 'gender', 'nationality',
            'place_of_origin', 'address', 'expiry_date',
        ];

        return array_values(array_unique(array_filter(
            array_map(static fn ($field) => trim((string) $field), $fields),
            static fn ($field) => in_array($field, $allowed, true)
        )));
    }

    private function mergeMissingFields(array $base, array $extra): array
    {
        foreach ($extra as $key => $value) {
            if ($key === 'document_side') {
                if (($base[$key] ?? 'unknown') === 'unknown' && in_array($value, ['front', 'back'], true)) {
                    $base[$key] = $value;
                }
                continue;
            }

            if (($base[$key] ?? '') === '' && $value !== '') {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    private function fullDocumentPrompt(): string
    {
        return <<<'PROMPT'
Bạn là bộ trích xuất dữ liệu giấy tờ tùy thân Việt Nam.
Hãy đọc ảnh CMND/CCCD/Thẻ căn cước được cung cấp và trả JSON đúng schema.

Quy tắc bắt buộc:
- Chỉ lấy dữ liệu nhìn thấy rõ trong ảnh; không suy đoán, không bịa, không tự hoàn thiện số hoặc địa chỉ.
- Nếu đây là mặt trước, phải cố gắng đọc đầy đủ: số CCCD, họ tên, ngày sinh, giới tính, quốc tịch, quê quán, nơi thường trú và ngày hết hạn.
- Không được bỏ qua ngày sinh, giới tính hoặc địa chỉ chỉ vì bố cục nhỏ; hãy đọc kỹ toàn bộ mặt thẻ.
- Trường nào thật sự không đọc được thì trả chuỗi rỗng.
- Giữ nguyên dấu tiếng Việt của họ tên và địa chỉ.
- Số CCCD chỉ gồm chữ số, không có khoảng trắng.
- Ngày tháng trả về theo YYYY-MM-DD khi đọc chắc chắn; nếu không chắc chắn thì để rỗng.
- gender chỉ trả một trong: male, female, other, hoặc chuỗi rỗng.
- document_side chỉ trả một trong: front, back, unknown.
- Không trả thêm lời giải thích ngoài JSON theo schema.
PROMPT;
    }

    private function frontFocusedPrompt(array $current): string
    {
        $knownCccd = $current['cccd'] !== '' ? "Số CCCD đã đọc được trước đó: {$current['cccd']}.\n" : '';
        $knownName = $current['full_name'] !== '' ? "Họ tên đã đọc được trước đó: {$current['full_name']}.\n" : '';

        return <<<PROMPT
Bạn đang xem ảnh mặt trước của giấy tờ tùy thân Việt Nam (CMND/CCCD/Thẻ căn cước).
{$knownCccd}{$knownName}Hãy tập trung thật kỹ vào các trường trên mặt trước và trả JSON đúng schema.

Bắt buộc:
- Ưu tiên đọc đủ ngày sinh, giới tính, quốc tịch, quê quán, nơi thường trú, ngày hết hạn.
- Nếu có nhiều ngày trên ảnh, birthday phải là dòng "Ngày sinh" / "Date of birth", không được nhầm với ngày hết hạn hoặc ngày cấp.
- address phải là "Nơi thường trú" / "Place of residence".
- place_of_origin phải là "Quê quán" / "Place of origin".
- expiry_date phải là ngày hết hạn thẻ nếu thấy rõ.
- Trường nào thật sự không nhìn rõ thì để chuỗi rỗng.
- Không suy đoán.
PROMPT;
    }

    private function birthdayFocusedPrompt(array $current): string
    {
        $context = [];
        if ($current['cccd'] !== '') {
            $context[] = 'Số CCCD đã đọc được: ' . $current['cccd'] . '.';
        }
        if ($current['full_name'] !== '') {
            $context[] = 'Họ tên đã đọc được: ' . $current['full_name'] . '.';
        }

        $contextText = $context !== [] ? implode(' ', $context) . "\n" : '';

        return <<<PROMPT
{$contextText}Bạn đang đọc ảnh mặt trước CCCD/CMND Việt Nam.
Chỉ tập trung tìm CHÍNH XÁC trường ngày sinh của chủ thẻ.

Quy tắc:
- birthday chỉ được lấy từ dòng "Ngày sinh" hoặc "Date of birth".
- Không lấy nhầm ngày hết hạn, ngày cấp hoặc bất kỳ ngày nào khác.
- Nếu không đọc chắc chắn thì trả chuỗi rỗng.
- Trả JSON đúng schema, không thêm lời giải thích.
PROMPT;
    }

    private function fullDocumentSchema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'cccd' => $string,
                'full_name' => $string,
                'birthday' => $string,
                'gender' => $string,
                'nationality' => $string,
                'place_of_origin' => $string,
                'address' => $string,
                'place_of_residence' => $string,
                'expiry_date' => $string,
                'issue_date' => $string,
                'identifying_characteristics' => $string,
                'document_side' => $string,
                'document_type' => $string,
                'confidence_note' => $string,
            ],
            'required' => [
                'cccd', 'full_name', 'birthday', 'gender', 'nationality',
                'place_of_origin', 'address', 'place_of_residence', 'expiry_date', 'issue_date',
                'identifying_characteristics', 'document_side', 'document_type', 'confidence_note',
            ],
        ];
    }

    private function frontFocusedSchema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'cccd' => $string,
                'full_name' => $string,
                'birthday' => $string,
                'gender' => $string,
                'nationality' => $string,
                'place_of_origin' => $string,
                'address' => $string,
                'place_of_residence' => $string,
                'expiry_date' => $string,
                'document_side' => $string,
                'confidence_note' => $string,
            ],
            'required' => [
                'cccd', 'full_name', 'birthday', 'gender', 'nationality',
                'place_of_origin', 'address', 'place_of_residence', 'expiry_date', 'document_side', 'confidence_note',
            ],
        ];
    }

    private function birthdayFocusedSchema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'birthday' => $string,
                'confidence_note' => $string,
            ],
            'required' => ['birthday', 'confidence_note'],
        ];
    }

    private function normalizePersonName(mixed $value): string
    {
        $value = $this->clean($value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_convert_case(
            mb_strtolower(trim($value), 'UTF-8'),
            MB_CASE_TITLE,
            'UTF-8'
        );
    }

    private function clean(mixed $value): string
    {
        $value = trim((string) $value);
        return in_array(mb_strtoupper($value, 'UTF-8'), ['N/A', 'NULL', 'UNKNOWN'], true) ? '' : $value;
    }

    private function normalizeDate(mixed $value): string
    {
        $value = $this->clean($value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/', '', $value) ?? $value;

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d', 'Y.m.d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
            }
        }

        if (preg_match('/\b(0?[1-9]|[12]\d|3[01])[\/\-.](0?[1-9]|1[0-2])[\/\-.]((?:19|20)\d{2})\b/u', $value, $match)) {
            return sprintf('%04d-%02d-%02d', (int) $match[3], (int) $match[2], (int) $match[1]);
        }

        return '';
    }

    private function normalizeGender(mixed $value): string
    {
        $value = mb_strtolower($this->clean($value), 'UTF-8');
        if (in_array($value, ['nam', 'male', 'm'], true)) return 'male';
        if (in_array($value, ['nữ', 'nu', 'female', 'f'], true)) return 'female';
        if (in_array($value, ['khác', 'khac', 'other'], true)) return 'other';
        return '';
    }

    private function normalizeDocumentSide(mixed $value): string
    {
        $value = mb_strtolower($this->clean($value), 'UTF-8');
        return in_array($value, ['front', 'back'], true) ? $value : 'unknown';
    }
}
