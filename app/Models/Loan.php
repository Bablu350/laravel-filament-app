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
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'loan_start_date' => 'date',
        'due_date' => 'date',
        'interest_rate' => 'decimal:2',
        'emi_amount' => 'decimal:2',
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
}
