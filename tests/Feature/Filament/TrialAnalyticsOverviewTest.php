<?php

use App\Filament\Widgets\TrialAnalyticsOverview;
use App\Models\Trial;
use App\Models\User;

it('counts started active ended and converted trials', function () {
    $activeUser = User::factory()->create();
    $expiredUser = User::factory()->create();
    $limitUser = User::factory()->create();
    $convertedUser = User::factory()->create();

    Trial::create([
        'user_id' => $activeUser->id,
        'status' => 'active',
        'started_at' => now()->subDay(),
        'expires_at' => now()->addDays(2),
        'jobs_used' => 1,
        'jobs_limit' => 3,
        'minutes_used' => 12,
        'minutes_limit' => 60,
    ]);

    Trial::create([
        'user_id' => $expiredUser->id,
        'status' => 'active',
        'started_at' => now()->subDays(4),
        'expires_at' => now()->subDay(),
        'jobs_used' => 0,
        'jobs_limit' => 3,
        'minutes_used' => 0,
        'minutes_limit' => 60,
    ]);

    Trial::create([
        'user_id' => $limitUser->id,
        'status' => 'limit_reached',
        'started_at' => now()->subHours(3),
        'expires_at' => now()->addDays(2),
        'jobs_used' => 3,
        'jobs_limit' => 3,
        'minutes_used' => 45,
        'minutes_limit' => 60,
        'limit_reached_at' => now()->subHour(),
    ]);

    Trial::create([
        'user_id' => $convertedUser->id,
        'status' => 'converted',
        'started_at' => now()->subDays(2),
        'expires_at' => now()->addDay(),
        'jobs_used' => 1,
        'jobs_limit' => 3,
        'minutes_used' => 20,
        'minutes_limit' => 60,
        'converted_at' => now(),
    ]);

    expect(TrialAnalyticsOverview::trialCounts())->toMatchArray([
        'total' => 4,
        'active' => 1,
        'ended' => 2,
        'converted' => 1,
        'conversion_rate' => 25.0,
    ]);
});
