<?php

namespace App\Support;

final class StaffShiftSchedule
{
    /**
     * Khung giờ vận hành dùng chung cho màn phân công.
     * full_day là phạm vi điều phối cả ngày, không phải một ca lao động 24 giờ.
     *
     * @return array<string, array{label:string,time:string,description:string}>
     */
    public static function definitions(): array
    {
        return [
            'morning' => [
                'label' => 'Ca sáng',
                'time' => '06:00–14:00',
                'description' => 'Ca 8 giờ, từ 06:00 đến 14:00.',
            ],
            'afternoon' => [
                'label' => 'Ca chiều',
                'time' => '14:00–22:00',
                'description' => 'Ca 8 giờ, từ 14:00 đến 22:00.',
            ],
            'evening' => [
                'label' => 'Ca tối',
                'time' => '22:00–06:00 hôm sau',
                'description' => 'Ca 8 giờ qua đêm, từ 22:00 đến 06:00 hôm sau.',
            ],
            'full_day' => [
                'label' => 'Cả ngày',
                'time' => '00:00–24:00',
                'description' => 'Phạm vi điều phối cả ngày; không dùng đồng thời với một ca khác.',
            ],
        ];
    }

    /** @return array<string, string> */
    public static function labels(bool $includeTime = true): array
    {
        $labels = [];

        foreach (self::definitions() as $key => $definition) {
            $labels[$key] = $includeTime
                ? $definition['label'] . ' · ' . $definition['time']
                : $definition['label'];
        }

        return $labels;
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    public static function label(string $shift, bool $includeTime = true): string
    {
        $definition = self::definitions()[$shift] ?? null;

        if (!$definition) {
            return $shift;
        }

        return $includeTime
            ? $definition['label'] . ' · ' . $definition['time']
            : $definition['label'];
    }

    public static function overlaps(string $left, string $right): bool
    {
        if ($left === 'full_day' || $right === 'full_day') {
            return true;
        }

        return $left === $right;
    }
}
