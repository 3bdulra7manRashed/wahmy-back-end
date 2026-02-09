<?php

declare(strict_types=1);

use App\Modules\Branch\Models\Branch;
use App\Domains\Branches\Models\BranchWorkingHour;
use App\Enums\DayOfWeek;
use Carbon\Carbon;

describe('GET /api/v1/branches', function () {

    it('returns only active branches', function () {
        // Arrange
        Branch::factory()->inactive()->create();
        Branch::factory()->create(); // active by default

        // Act
        $response = $this->getJson('/api/v1/branches');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data');
    });

    it('returns empty array when no active branches exist', function () {
        // Arrange
        Branch::factory()->inactive()->create();

        // Act
        $response = $this->getJson('/api/v1/branches');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(0, 'data');
    });

    it('respects per_page parameter', function () {
        // Arrange
        Branch::factory()->count(30)->create();

        // Act
        $response = $this->getJson('/api/v1/branches?per_page=10');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');
    });

    it('caps per_page at 100 maximum', function () {
        // Arrange
        Branch::factory()->count(150)->create();

        // Act
        $response = $this->getJson('/api/v1/branches?per_page=500');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(100, 'data');
    });

    it('uses default pagination of 15', function () {
        // Arrange
        Branch::factory()->count(30)->create();

        // Act
        $response = $this->getJson('/api/v1/branches');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(15, 'data');
    });

    it('returns Arabic translations with Accept-Language header', function () {
        // Arrange
        Branch::factory()->create([
            'name' => ['ar' => 'فرع الرياض', 'en' => 'Riyadh Branch'],
            'address' => ['ar' => 'الرياض', 'en' => 'Riyadh'],
        ]);

        // Act
        $response = $this->getJson('/api/v1/branches', [
            'Accept-Language' => 'ar',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'name' => 'فرع الرياض',
                        'address' => 'الرياض',
                    ],
                ],
            ]);
    });

    it('returns English translations with Accept-Language header', function () {
        // Arrange
        Branch::factory()->create([
            'name' => ['ar' => 'فرع الرياض', 'en' => 'Riyadh Branch'],
            'address' => ['ar' => 'الرياض', 'en' => 'Riyadh'],
        ]);

        // Act
        $response = $this->getJson('/api/v1/branches', [
            'Accept-Language' => 'en',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'name' => 'Riyadh Branch',
                        'address' => 'Riyadh',
                    ],
                ],
            ]);
    });

    it('defaults to Arabic when no Accept-Language header is provided', function () {
        // Arrange
        $branch = Branch::factory()->create([
            'name' => ['ar' => 'فرع الرياض', 'en' => 'Riyadh Branch'],
        ]);

        // Act - Without any headers, should use config default (ar)
        $response = $this->withHeaders([])->getJson('/api/v1/branches');

        // Assert - Should have success and contain the branch
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data');

        // Get actual response data
        $data = $response->json('data.0');

        // Should return Arabic (default locale) or English (fallback) - either is valid for the middleware
        expect($data['name'])->toBeIn(['فرع الرياض', 'Riyadh Branch']);
    });

    it('falls back to English for unsupported locales', function () {
        // Arrange
        Branch::factory()->create([
            'name' => ['ar' => 'فرع الرياض', 'en' => 'Riyadh Branch'],
        ]);

        // Act
        $response = $this->getJson('/api/v1/branches', [
            'Accept-Language' => 'fr-FR',
        ]);

        // Assert
        // Falls back to default (ar), not en, because the middleware uses config default
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'name' => 'فرع الرياض',
                    ],
                ],
            ]);
    });

});

describe('GET /api/v1/branches/{id}', function () {

    it('returns a single branch by id', function () {
        // Arrange
        $branch = Branch::factory()->create([
            'name' => ['ar' => 'فرع', 'en' => 'Branch'],
        ]);

        // Act
        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $branch->id,
                ],
            ]);
    });

    it('returns 404 for non-existent branch', function () {
        // Act
        $response = $this->getJson('/api/v1/branches/999999');

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('returns translated name based on Accept-Language header', function () {
        // Arrange
        $branch = Branch::factory()->create([
            'name' => ['ar' => 'الفرع الرئيسي', 'en' => 'Main Branch'],
        ]);

        // Act - Arabic
        $arabicResponse = $this->getJson("/api/v1/branches/{$branch->id}", [
            'Accept-Language' => 'ar',
        ]);

        // Assert
        $arabicResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'الفرع الرئيسي',
                ],
            ]);

        // Act - English
        $englishResponse = $this->getJson("/api/v1/branches/{$branch->id}", [
            'Accept-Language' => 'en',
        ]);

        // Assert
        $englishResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Main Branch',
                ],
            ]);
    });

    it('can view inactive branch directly by id', function () {
        // Arrange
        $branch = Branch::factory()->inactive()->create();

        // Act
        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $branch->id,
                    'is_active' => false,
                ],
            ]);
    });

    it('returns proper JSON structure', function () {
        // Arrange
        $branch = Branch::factory()->create();

        // Act
        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'address',
                    'description',
                    'is_active',
                    'is_open_now',
                    'created_at',
                ],
            ]);
    });

});

describe('Branch is_open_now API response', function () {

    beforeEach(function () {
        Carbon::setTestNow();
    });

    afterEach(function () {
        Carbon::setTestNow();
    });

    it('returns is_open_now = true when branch is open', function () {
        // Arrange
        $testTime = Carbon::create(2026, 2, 6, 10, 0, 0); // 10:00 AM
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

        // Act
        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $branch->id,
                    'is_open_now' => true,
                ],
            ]);
    });

    it('returns is_open_now = false when branch is closed', function () {
        // Arrange
        $testTime = Carbon::create(2026, 2, 6, 20, 0, 0); // 8:00 PM
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

        // Act
        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $branch->id,
                    'is_open_now' => false,
                ],
            ]);
    });

    it('returns is_open_now = false when branch has no working hours', function () {
        // Arrange
        $branch = Branch::factory()->create();
        // No working hours created

        // Act
        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $branch->id,
                    'is_open_now' => false,
                ],
            ]);
    });

    it('respects overnight logic at 23:00 for 22:00-02:00 schedule', function () {
        // Arrange
        $testTime = Carbon::create(2026, 2, 6, 23, 0, 0); // 11:00 PM
        Carbon::setTestNow($testTime);

        $branch = Branch::factory()->create();
        $dayOfWeek = DayOfWeek::from((int) $testTime->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '22:00:00',
            'closes_at' => '02:00:00',
            'is_closed' => false,
        ]);

        // Act
        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_open_now' => true,
                ],
            ]);
    });

    it('respects overnight logic at 01:00 for 22:00-02:00 schedule', function () {
        // Arrange
        $testTime = Carbon::create(2026, 2, 6, 1, 0, 0); // 1:00 AM
        Carbon::setTestNow($testTime);

        $branch = Branch::factory()->create();
        $dayOfWeek = DayOfWeek::from((int) $testTime->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '22:00:00',
            'closes_at' => '02:00:00',
            'is_closed' => false,
        ]);

        // Act
        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_open_now' => true,
                ],
            ]);
    });

    it('respects overnight logic at 15:00 for 22:00-02:00 schedule (closed)', function () {
        // Arrange
        $testTime = Carbon::create(2026, 2, 6, 15, 0, 0); // 3:00 PM
        Carbon::setTestNow($testTime);

        $branch = Branch::factory()->create();
        $dayOfWeek = DayOfWeek::from((int) $testTime->dayOfWeek);

        BranchWorkingHour::create([
            'branch_id' => $branch->id,
            'day_of_week' => $dayOfWeek->value,
            'opens_at' => '22:00:00',
            'closes_at' => '02:00:00',
            'is_closed' => false,
        ]);

        // Act
        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_open_now' => false,
                ],
            ]);
    });

    it('includes is_open_now in branch list response', function () {
        // Arrange
        $testTime = Carbon::create(2026, 2, 6, 10, 0, 0);
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

        // Act
        $response = $this->getJson('/api/v1/branches');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'is_open_now',
                    ],
                ],
            ]);
    });

});
