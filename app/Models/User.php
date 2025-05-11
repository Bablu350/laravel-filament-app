<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'date_of_birth',
        'aadhaar_number',
        'aadhaar_card',
        'pincode',
        'address',
        'bank_account_number',
        'ifsc_code',
        'bank_details',
        'pan_number',
        'pan_card',
        'voter_id_number',
        'voter_id_card',
        'created_by',
        'updated_by',
        'deleted_by',
        'p_info_verified',
        'doc_verified',
        'address_verified',
        'bank_verified',
        'user_verified',
        'p_info_verified_by',
        'doc_verified_by',
        'address_verified_by',
        'bank_verified_by',
        'user_verified_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'bank_details' => 'array',
            'p_info_verified' => 'boolean',
            'doc_verified' => 'boolean',
            'address_verified' => 'boolean',
            'bank_verified' => 'boolean',
            'user_verified' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function p_info_verifier()
    {
        return $this->belongsTo(User::class, 'p_info_verified_by');
    }

    public function doc_verifier()
    {
        return $this->belongsTo(User::class, 'doc_verified_by');
    }

    public function address_verifier()
    {
        return $this->belongsTo(User::class, 'address_verified_by');
    }

    public function bank_verifier()
    {
        return $this->belongsTo(User::class, 'bank_verified_by');
    }

    public function user_verifier()
    {
        return $this->belongsTo(User::class, 'user_verified_by');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (auth()->check()) {
                $user->created_by = auth()->id();
                $user->updated_by = auth()->id();
            }
            $user->updateVerifiedBy();
        });

        static::updating(function ($user) {
            if (auth()->check()) {
                $user->updated_by = auth()->id();
            }
            $user->updateVerifiedBy();
        });

        static::deleting(function ($user) {
            if (auth()->check()) {
                $user->deleted_by = auth()->id();
                $user->save();
            }
        });
    }

    protected function updateVerifiedBy()
    {
        if (auth()->check()) {
            $userId = auth()->id();

            if ($this->isDirty('p_info_verified')) {
                $this->p_info_verified_by = $this->p_info_verified ? $userId : null;
            }

            if ($this->isDirty('doc_verified')) {
                $this->doc_verified_by = $this->doc_verified ? $userId : null;
            }

            if ($this->isDirty('address_verified')) {
                $this->address_verified_by = $this->address_verified ? $userId : null;
            }

            if ($this->isDirty('bank_verified')) {
                $this->bank_verified_by = $this->bank_verified ? $userId : null;
            }

            if ($this->isDirty('user_verified')) {
                $this->user_verified_by = $this->user_verified ? $userId : null;
            }
        }
    }
}
