<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case CUSTOMER = 'customer';

    /**
     * Get the human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::CUSTOMER => 'Customer',
        };
    }

    /**
     * Get all role values as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if this role can access the admin panel.
     */
    public function canAccessAdminPanel(): bool
    {
        return $this === self::ADMIN;
    }
}
