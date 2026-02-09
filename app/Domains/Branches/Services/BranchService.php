<?php

declare(strict_types=1);

namespace App\Domains\Branches\Services;

use App\Modules\Branch\Models\Branch;
use App\Domains\Branches\Models\BranchWorkingHour;
use App\Enums\DayOfWeek;
use App\Support\BaseDomainService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BranchService extends BaseDomainService
{
    /**
     * Create a new branch.
     *
     * @param array<string, mixed> $data
     */
    public function createBranch(array $data): Branch
    {
        return Branch::create([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Update an existing branch.
     *
     * @param array<string, mixed> $data
     */
    public function updateBranch(Branch $branch, array $data): Branch
    {
        $branch->update([
            'name' => $data['name'] ?? $branch->name,
            'address' => $data['address'] ?? $branch->address,
            'is_active' => $data['is_active'] ?? $branch->is_active,
        ]);

        return $branch->fresh();
    }

    /**
     * Deactivate a branch.
     */
    public function deactivateBranch(Branch $branch): Branch
    {
        $branch->update(['is_active' => false]);

        return $branch->fresh();
    }

    /**
     * Activate a branch.
     */
    public function activateBranch(Branch $branch): Branch
    {
        $branch->update(['is_active' => true]);

        return $branch->fresh();
    }

    /**
     * Delete a branch.
     */
    public function deleteBranch(Branch $branch): bool
    {
        return $branch->delete();
    }

    /**
     * Get all active branches.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Branch>
     */
    public function getActiveBranches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::active()->get();
    }

    /**
     * Find a branch by ID.
     */
    public function findBranch(int $id): ?Branch
    {
        return Branch::find($id);
    }

    /**
     * Set working hours for a branch.
     *
     * @param array<int, array{day_of_week: int, opens_at: string|null, closes_at: string|null, is_closed: bool}> $data
     */
    public function setWorkingHours(Branch $branch, array $data): void
    {
        DB::transaction(function () use ($branch, $data): void {
            foreach ($data as $dayData) {
                BranchWorkingHour::updateOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'day_of_week' => $dayData['day_of_week'],
                    ],
                    [
                        'opens_at' => $dayData['opens_at'] ?? null,
                        'closes_at' => $dayData['closes_at'] ?? null,
                        'is_closed' => $dayData['is_closed'] ?? false,
                    ]
                );
            }
        });
    }

    /**
     * Open a specific day for a branch.
     */
    public function openDay(Branch $branch, DayOfWeek $day, string $opensAt, string $closesAt): void
    {
        if ($opensAt === $closesAt) {
            throw new InvalidArgumentException('Opening time cannot be the same as closing time.');
        }

        BranchWorkingHour::updateOrCreate(
            [
                'branch_id' => $branch->id,
                'day_of_week' => $day->value,
            ],
            [
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
                'is_closed' => false,
            ]
        );
    }

    /**
     * Close a specific day for a branch.
     */
    public function closeDay(Branch $branch, DayOfWeek $day): void
    {
        BranchWorkingHour::updateOrCreate(
            [
                'branch_id' => $branch->id,
                'day_of_week' => $day->value,
            ],
            [
                'opens_at' => null,
                'closes_at' => null,
                'is_closed' => true,
            ]
        );
    }
}
