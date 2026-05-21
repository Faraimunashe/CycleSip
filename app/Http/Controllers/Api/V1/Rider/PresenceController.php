<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Events\RiderStatusUpdated;
use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\RiderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    use RespondsWithJson;

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_online' => ['required', 'boolean'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $riderProfile = RiderProfile::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['approval_status' => 'pending']
        );

        $riderProfile->update([
            'is_online' => $validated['is_online'],
        ]);

        if (isset($validated['latitude'], $validated['longitude'])) {
            $riderProfile->locations()->create([
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'recorded_at' => now(),
            ]);
        }

        event(new RiderStatusUpdated($riderProfile->fresh()));

        return $this->ok([
            'rider_profile_id' => $riderProfile->id,
            'is_online' => $riderProfile->is_online,
        ], 'Rider presence updated.');
    }
}
