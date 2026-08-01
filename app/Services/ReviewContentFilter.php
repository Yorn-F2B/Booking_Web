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
        $normalized = preg_replace('/[\p{P}\p{S}_]+/u', ' ', $text);
        $normalized = preg_replace('/\s+/u', ' ', $normalized);

        $words = Schema::hasTable('banned_words')
            ? BannedWord::query()->pluck('word')->all()
            : (array) config('banned_words.words', []);

        foreach ($words as $word) {
            $word = trim(mb_strtolower((string) $word, 'UTF-8'));
            if ($word !== '' && preg_match('/(?<![\pL\pN])' . preg_quote($word, '/') . '(?![\pL\pN])/u', $normalized)) {
                throw ValidationException::withMessages([
                    'comment' => 'Đánh giá chứa từ ngữ không phù hợp ("' . $word . '"). Nội dung chưa được lưu.',
                ]);
            }
        }
    }
}
