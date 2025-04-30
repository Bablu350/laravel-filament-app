<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'loan_amount',
        'loan_start_date',
        'due_date',
        'loan_age',
        'interest_rate',
        'emi_type',
        'emi_amount',
        'total_amount_paid'
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'loan_start_date' => 'date',
        'due_date' => 'date',
        'interest_rate' => 'decimal:2',
        'emi_amount' => 'decimal:2',
        'total_amount_paid' => 'decimal:2',
        'emi_type' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emis(): HasMany
    {
        return $this->hasMany(Emi::class);
    }

    // Automatically update total_amount_paid when EMIs are updated
    protected static function booted()
    {
        static::saved(function ($loan) {
            $newTotal = $loan->emis()->sum('emi_paid_amount');
            if ($loan->total_amount_paid != $newTotal) {
                $loan->total_amount_paid = $newTotal;
                $loan->saveQuietly(); // Save without triggering events
            }
        });
    }
}
