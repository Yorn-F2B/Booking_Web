<?php

namespace App\Services;

use App\Models\BannedWord;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ReviewContentFilter
{
    public function assertClean(?string ...$parts): void
    {
        $text = mb_strtolower(implode(' ', array_filter($parts)), 'UTF-8');
        $normalized = preg_replace('/[\p{P}\p{S}_]+/u', ' ', $text) ?? $text;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        $words = Schema::hasTable('banned_words')
            ? BannedWord::query()->pluck('word')->all()
            : (array) config('banned_words.words', []);

        foreach ($words as $word) {
            $word = trim(mb_strtolower((string) $word, 'UTF-8'));
            if ($word === '') {
                continue;
            }

            // Bắt dạng bình thường trước để giữ ranh giới từ rõ ràng.
            if (preg_match('/(?<![\pL\pN])' . preg_quote($word, '/') . '(?![\pL\pN])/u', $normalized)) {
                $this->reject($word);
            }

            // Bắt các kiểu lách đơn giản: "đ . ị . t", "f u c k", "chó---chết".
            // Chỉ cho phép khoảng trắng/dấu/ký hiệu chen giữa các ký tự, không bỏ qua
            // chữ hoặc số khác nên tránh biến bộ lọc thành kiểm duyệt mơ hồ.
            $characters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $characters = array_values(array_filter($characters, fn (string $char) => !preg_match('/\s/u', $char)));
            if (count($characters) < 2) {
                continue;
            }

            $separator = '[\s\p{P}\p{S}_]*';
            $obfuscatedPattern = '(?<![\pL\pN])'
                . implode($separator, array_map(fn (string $char) => preg_quote($char, '/'), $characters))
                . '(?![\pL\pN])';

            if (preg_match('/' . $obfuscatedPattern . '/u', $text)) {
                $this->reject($word);
            }
        }
    }

    private function reject(string $word): never
    {
        throw ValidationException::withMessages([
            'comment' => 'Đánh giá chứa từ ngữ không phù hợp ("' . $word . '"). Nội dung chưa được lưu.',
        ]);
    }
}
