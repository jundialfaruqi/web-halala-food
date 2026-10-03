<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'is_system',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function journalItems(): HasMany
    {
        return $this->hasMany(JournalItem::class);
    }

    /**
     * Calculate current balance based on normal balance.
     */
    public function getBalanceAttribute(): float
    {
        if ($this->relationLoaded('journalItems')) {
            $totalDebit = (float) $this->journalItems->sum('debit');
            $totalCredit = (float) $this->journalItems->sum('credit');
        } else {
            $totalDebit = (float) $this->journalItems()->sum('debit');
            $totalCredit = (float) $this->journalItems()->sum('credit');
        }

        if ($this->normal_balance === 'debit') {
            return $totalDebit - $totalCredit;
        }

        return $totalCredit - $totalDebit;
    }
}
