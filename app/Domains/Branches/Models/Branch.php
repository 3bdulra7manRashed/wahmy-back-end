<?php

declare(strict_types=1);

namespace App\Domains\Branches\Models;

use App\Enums\DayOfWeek;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\BranchFactory
    {
        return \Database\Factories\BranchFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'description',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'address' => 'array',
            'description' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get a translated value for the given field.
     *
     * @param string $field The field name (name, address, description)
     * @param string|null $locale The locale to retrieve (defaults to app locale)
     */
    public function getTranslated(string $field, ?string $locale = null): ?string
    {
        $value = $this->{$field};

        if (!is_array($value)) {
            return null;
        }

        $locale = $locale ?? app()->getLocale();

        // Try requested locale
        if (isset($value[$locale])) {
            return $value[$locale];
        }

        // Fallback to English
        if (isset($value['en'])) {
            return $value['en'];
        }

        // Return first available value
        return $value[array_key_first($value)] ?? null;
    }

    /**
     * Scope to get only active branches.
     *
     * @param Builder<Branch> $query
     * @return Builder<Branch>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only inactive branches.
     *
     * @param Builder<Branch> $query
     * @return Builder<Branch>
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Get the working hours for the branch.
     *
     * @return HasMany<BranchWorkingHour, $this>
     */
    public function workingHours(): HasMany
    {
        return $this->hasMany(BranchWorkingHour::class);
    }

    /**
     * Check if the branch is currently open.
     */
    public function isOpenNow(): bool
    {
        return $this->isOpenAt(now());
    }

    /**
     * Check if the branch is open at the given time.
     */
    public function isOpenAt(Carbon $time): bool
    {
        $dayOfWeek = DayOfWeek::from((int) $time->dayOfWeek);

        /** @var BranchWorkingHour|null $workingHour */
        $workingHour = $this->workingHours()
            ->where('day_of_week', $dayOfWeek->value)
            ->first();

        if ($workingHour === null) {
            return false;
        }

        if ($workingHour->is_closed) {
            return false;
        }

        if ($workingHour->opens_at === null || $workingHour->closes_at === null) {
            return false;
        }

        $opens = $time->copy()->setTimeFromTimeString($workingHour->opens_at);
        $closes = $time->copy()->setTimeFromTimeString($workingHour->closes_at);

        // Handle overnight closing (e.g., opens 22:00, closes 02:00)
        if ($closes->lt($opens)) {
            // Open if current time is after opening OR before closing (next day)
            return $time->gte($opens) || $time->lt($closes);
        }

        // Normal hours: check if current time is within range [opens, closes)
        return $time->between($opens, $closes, false) && $time->lt($closes);
    }
}
