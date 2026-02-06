<?php

declare(strict_types=1);

use App\Domains\Branches\Models\Branch;
use App\Domains\Branches\Models\BranchWorkingHour;
use App\Enums\DayOfWeek;
use Carbon\Carbon;

beforeEach(function () {
    // Reset Carbon test time before each test
    Carbon::setTestNow();
});

afterEach(function () {
    // Clean up Carbon test time after each test
    Carbon::setTestNow();
});

describe('Branch::isOpenAt()', function () {

    it('returns true when branch is open during normal hours', function () {
        // Arrange
        $branch = Branch::factory()->create();
        $today = Carbon::today();
        $dayOfWeek = DayOfWeek::from((int) $today->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '09:00:00',
            'closes_at' => '18:00:00',
            'is_closed' => false,
        ]);

        // Act & Assert
        $openTime = $today->copy()->setTime(10, 0);
        $closedTime = $today->copy()->setTime(20, 0);

        expect($branch->isOpenAt($openTime))->toBeTrue();
        expect($branch->isOpenAt($closedTime))->toBeFalse();
    });

    it('returns false when time is exactly at closing time', function () {
        // Arrange
        $branch = Branch::factory()->create();
        $today = Carbon::today();
        $dayOfWeek = DayOfWeek::from((int) $today->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '09:00:00',
            'closes_at' => '18:00:00',
            'is_closed' => false,
        ]);

        // Act & Assert
        $closingTime = $today->copy()->setTime(18, 0);
        expect($branch->isOpenAt($closingTime))->toBeFalse();
    });

    it('returns false when time is exactly at opening time (exclusive start)', function () {
        // Arrange
        $branch = Branch::factory()->create();
        $today = Carbon::today();
        $dayOfWeek = DayOfWeek::from((int) $today->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '09:00:00',
            'closes_at' => '18:00:00',
            'is_closed' => false,
        ]);

        // Act & Assert
        // Note: Implementation uses exclusive boundaries (opens_at < time < closes_at)
        $openingTime = $today->copy()->setTime(9, 0);
        expect($branch->isOpenAt($openingTime))->toBeFalse();
    });

    it('returns false when is_closed is true', function () {
        // Arrange
        $branch = Branch::factory()->create();
        $today = Carbon::today();
        $dayOfWeek = DayOfWeek::from((int) $today->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '09:00:00',
            'closes_at' => '18:00:00',
            'is_closed' => true,
        ]);

        // Act & Assert
        $duringHours = $today->copy()->setTime(12, 0);
        expect($branch->isOpenAt($duringHours))->toBeFalse();
    });

    it('returns false when no working hour record exists', function () {
        // Arrange
        $branch = Branch::factory()->create();
        $today = Carbon::today();

        // No working hours created

        // Act & Assert
        $anyTime = $today->copy()->setTime(12, 0);
        expect($branch->isOpenAt($anyTime))->toBeFalse();
    });

    it('returns false when opens_at is null', function () {
        // Arrange
        $branch = Branch::factory()->create();
        $today = Carbon::today();
        $dayOfWeek = DayOfWeek::from((int) $today->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => null,
            'closes_at' => '18:00:00',
            'is_closed' => false,
        ]);

        // Act & Assert
        $anyTime = $today->copy()->setTime(12, 0);
        expect($branch->isOpenAt($anyTime))->toBeFalse();
    });

    it('returns false when closes_at is null', function () {
        // Arrange
        $branch = Branch::factory()->create();
        $today = Carbon::today();
        $dayOfWeek = DayOfWeek::from((int) $today->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '09:00:00',
            'closes_at' => null,
            'is_closed' => false,
        ]);

        // Act & Assert
        $anyTime = $today->copy()->setTime(12, 0);
        expect($branch->isOpenAt($anyTime))->toBeFalse();
    });

});

describe('Branch::isOpenAt() overnight logic', function () {

    it('returns true during late night hours for overnight schedule', function () {
        // Arrange: Branch open from 22:00 to 02:00 (next day)
        $branch = Branch::factory()->create();
        $today = Carbon::today();
        $dayOfWeek = DayOfWeek::from((int) $today->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '22:00:00',
            'closes_at' => '02:00:00',
            'is_closed' => false,
        ]);

        // Act & Assert
        $lateNight = $today->copy()->setTime(23, 0);
        expect($branch->isOpenAt($lateNight))->toBeTrue();
    });

    it('returns true during early morning hours for overnight schedule', function () {
        // Arrange: Branch open from 22:00 to 02:00 (next day)
        $branch = Branch::factory()->create();
        $today = Carbon::today();
        $dayOfWeek = DayOfWeek::from((int) $today->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '22:00:00',
            'closes_at' => '02:00:00',
            'is_closed' => false,
        ]);

        // Act & Assert
        $earlyMorning = $today->copy()->setTime(1, 0);
        expect($branch->isOpenAt($earlyMorning))->toBeTrue();
    });

    it('returns false during daytime for overnight schedule', function () {
        // Arrange: Branch open from 22:00 to 02:00 (next day)
        $branch = Branch::factory()->create();
        $today = Carbon::today();
        $dayOfWeek = DayOfWeek::from((int) $today->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '22:00:00',
            'closes_at' => '02:00:00',
            'is_closed' => false,
        ]);

        // Act & Assert
        $afternoon = $today->copy()->setTime(15, 0);
        expect($branch->isOpenAt($afternoon))->toBeFalse();
    });

    it('returns false at exactly closing time for overnight schedule', function () {
        // Arrange
        $branch = Branch::factory()->create();
        $today = Carbon::today();
        $dayOfWeek = DayOfWeek::from((int) $today->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '22:00:00',
            'closes_at' => '02:00:00',
            'is_closed' => false,
        ]);

        // Act & Assert
        $closingTime = $today->copy()->setTime(2, 0);
        expect($branch->isOpenAt($closingTime))->toBeFalse();
    });

});

describe('Branch::isOpenNow()', function () {

    it('returns true when current time is within working hours', function () {
        // Arrange
        $testTime = Carbon::create(2026, 2, 6, 12, 0, 0); // Thursday at noon
        Carbon::setTestNow($testTime);

        $branch = Branch::factory()->create();
        $dayOfWeek = DayOfWeek::from((int) $testTime->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '09:00:00',
            'closes_at' => '18:00:00',
            'is_closed' => false,
        ]);

        // Act & Assert
        expect($branch->isOpenNow())->toBeTrue();
    });

    it('returns false when current time is outside working hours', function () {
        // Arrange
        $testTime = Carbon::create(2026, 2, 6, 20, 0, 0); // Thursday at 8 PM
        Carbon::setTestNow($testTime);

        $branch = Branch::factory()->create();
        $dayOfWeek = DayOfWeek::from((int) $testTime->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '09:00:00',
            'closes_at' => '18:00:00',
            'is_closed' => false,
        ]);

        // Act & Assert
        expect($branch->isOpenNow())->toBeFalse();
    });

    it('returns false when branch is marked as closed', function () {
        // Arrange
        $testTime = Carbon::create(2026, 2, 6, 12, 0, 0); // Thursday at noon
        Carbon::setTestNow($testTime);

        $branch = Branch::factory()->create();
        $dayOfWeek = DayOfWeek::from((int) $testTime->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '09:00:00',
            'closes_at' => '18:00:00',
            'is_closed' => true,
        ]);

        // Act & Assert
        expect($branch->isOpenNow())->toBeFalse();
    });

    it('delegates to isOpenAt with current time', function () {
        // Arrange
        $testTime = Carbon::create(2026, 2, 6, 10, 30, 0); // Thursday at 10:30 AM
        Carbon::setTestNow($testTime);

        $branch = Branch::factory()->create();
        $dayOfWeek = DayOfWeek::from((int) $testTime->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '09:00:00',
            'closes_at' => '18:00:00',
            'is_closed' => false,
        ]);

        // Act & Assert
        expect($branch->isOpenNow())->toBe($branch->isOpenAt($testTime));
    });

});
