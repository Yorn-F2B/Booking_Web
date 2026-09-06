<?php

namespace App\Services;

use App\Models\BannedWord;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ReviewContentFilter
{
    public function assertClean(?string ...$parts): void
    {
        $text = implode(' ', array_filter($parts, fn ($part) => $part !== null && $part !== ''));
        $foldedText = $this->foldVietnamese($text);
        $normalizedText = preg_replace('/[\p{P}\p{S}_]+/u', ' ', $foldedText) ?? $foldedText;
        $normalizedText = preg_replace('/\s+/u', ' ', $normalizedText) ?? $normalizedText;

        // Luôn gộp cấu hình mặc định với danh sách quản trị thêm trong DB.
        $words = collect((array) config('banned_words.words', []));
        if (Schema::hasTable('banned_words')) {
            $words = $words->merge(BannedWord::query()->pluck('word'));
        }

        $words = $words
            ->map(fn ($word) => trim((string) $word))
            ->filter()
            ->unique(fn ($word) => $this->foldVietnamese((string) $word))
            ->values()
            ->all();

        foreach ($words as $word) {
            $originalWord = trim((string) $word);
            $foldedWord = trim($this->foldVietnamese($originalWord));
            if ($foldedWord === '') {
                continue;
            }

            // So khớp trên dạng đã bỏ khác biệt Unicode/dấu tiếng Việt. Ví dụ
            // "buồi" và "buồi" (ký tự ghép) đều trở thành cùng một chuỗi.
            if (preg_match('/(?<![a-z0-9])' . preg_quote($foldedWord, '/') . '(?![a-z0-9])/u', $normalizedText)) {
                $this->reject($originalWord);
            }

            // Bắt các kiểu lách đơn giản như "đ . ị . t", "f u c k", "chó---chết".
            $characters = preg_split('//u', $foldedWord, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $characters = array_values(array_filter($characters, fn (string $char) => !preg_match('/\s/u', $char)));
            if (count($characters) < 2) {
                continue;
            }

            $separator = '[\s\p{P}\p{S}_]*';
            $obfuscatedPattern = '(?<![a-z0-9])'
                . implode($separator, array_map(fn (string $char) => preg_quote($char, '/'), $characters))
                . '(?![a-z0-9])';

            if (preg_match('/' . $obfuscatedPattern . '/u', $foldedText)) {
                $this->reject($originalWord);
            }
        }
    }

    private function foldVietnamese(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');

        // Xử lý chữ tiếng Việt dựng sẵn (precomposed).
        $value = strtr($value, [
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
        ]);

        // Xử lý dạng Unicode tổ hợp (ví dụ o + dấu mũ + dấu huyền).
        $value = preg_replace('/\p{M}+/u', '', $value) ?? $value;

        return $value;
    }

    private function reject(string $word): never
    {
        throw ValidationException::withMessages([
            'comment' => 'Đánh giá chứa từ ngữ không phù hợp ("' . $word . '"). Nội dung chưa được lưu.',
        ]);
    }
}
