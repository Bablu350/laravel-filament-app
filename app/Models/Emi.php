<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Emi extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'due_date',
        'emi_amount',
        'emi_paid_amount',
        'payment_date',
    ];

    protected $casts = [
        'due_date' => 'date',
        'emi_amount' => 'decimal:2',
        'emi_paid_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
