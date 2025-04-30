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
        'fine',
        'payment_date',
    ];

    protected $casts = [
        'due_date' => 'date',
        'emi_amount' => 'decimal:2',
        'emi_paid_amount' => 'decimal:2',
        'fine' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    // Automatically calculate fine when emi_paid_amount is updated
    protected static function booted()
    {
        static::saving(function ($emi) {
            $emi->fine = ($emi->emi_paid_amount - $emi->emi_amount >= 0 ? $emi->emi_paid_amount - $emi->emi_amount : 0);
        });

        static::saved(function ($emi) {
            // Trigger Loan's total_amount_paid update
            $emi->loan->save();
        });
    }
}
