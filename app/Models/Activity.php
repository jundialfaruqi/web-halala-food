<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'description',
        'user_id',
        'courier_id',
        'subject_type',
        'subject_id',
        'data',
    ];

    protected $appends = [
        'user_initials',
        'time_formatted',
        'date_formatted',
        'date_group_key',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUserInitialsAttribute(): string
    {
        $name = trim($this->user?->name ?? 'User');
        $words = preg_split('/\s+/', $name);
        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }
        return mb_strtoupper(mb_substr($name, 0, min(2, mb_strlen($name))));
    }

    public function getTimeFormattedAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('H:i') : '';
    }

    public function getDateFormattedAttribute(): string
    {
        return $this->created_at ? $this->created_at->translatedFormat('d M Y') : '';
    }

    public function getDateGroupKeyAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('Y-m-d') : '';
    }
}
