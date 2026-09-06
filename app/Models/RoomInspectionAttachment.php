<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomInspectionAttachment extends Model
{
    public const CONTEXT_INITIAL_REPORT = 'initial_report';
    public const CONTEXT_SUPPLEMENTAL_REPORT = 'supplemental_report';
    public const CONTEXT_RECHECK_REPORT = 'recheck_report';

    protected $fillable = [
        'room_inspection_id',
        'context',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'uploaded_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function inspection()
    {
        return $this->belongsTo(RoomInspection::class, 'room_inspection_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public static function contextOptions(): array
    {
        return [
            self::CONTEXT_INITIAL_REPORT => 'Ảnh kiểm tra ban đầu',
            self::CONTEXT_SUPPLEMENTAL_REPORT => 'Ảnh phát hiện bổ sung',
            self::CONTEXT_RECHECK_REPORT => 'Ảnh kiểm tra lại',
        ];
    }

    public function contextLabel(): string
    {
        return self::contextOptions()[$this->context] ?? 'Ảnh minh chứng';
    }
}
