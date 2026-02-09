<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'code',
        'expires_at',
        'attempts',
        'verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Check if the OTP has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the OTP has been verified.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Check if max attempts have been exceeded.
     */
    public function hasExceededAttempts(int $maxAttempts = 5): bool
    {
        return $this->attempts >= $maxAttempts;
    }

    /**
     * Increment the attempts counter.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Mark the OTP as verified.
     */
    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }
}
