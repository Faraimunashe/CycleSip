<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\RiderProfile;
use App\Services\RiderLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly RiderLocationService $riderLocationService,
    ) {
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $riderProfile = RiderProfile::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['approval_status' => 'pending'],
        );

        $location = $this->riderLocationService->record(
            profile: $riderProfile,
            latitude: (float) $validated['latitude'],
            longitude: (float) $validated['longitude'],
        );

        return $this->ok([
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'recorded_at' => optional($location->recorded_at)?->toIso8601String(),
        ], 'Rider location updated.');
    }
}
