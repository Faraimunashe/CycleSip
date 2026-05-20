<?php

namespace App\Http\Controllers\Admin;

use App\Events\RiderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveRiderRequest;
use App\Models\RiderProfile;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiderManagementController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function index(Request $request): Response
    {
        $riders = RiderProfile::query()
            ->with(['user', 'zones'])
            ->when($request->string('approval_status')->toString(), fn ($query, $status) => $query->where('approval_status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (RiderProfile $profile): array => $this->toRiderArray($profile));

        return Inertia::render('Admin/Riders/Index', [
            'riders' => $riders,
            'filters' => $request->only('approval_status'),
        ]);
    }

    public function show(RiderProfile $rider): Response
    {
        $rider->load(['user', 'zones', 'documents']);

        return Inertia::render('Admin/Riders/Show', [
            'rider' => $this->toRiderArray($rider, true),
        ]);
    }

    public function edit(RiderProfile $rider): Response
    {
        $rider->load(['user', 'zones']);

        return Inertia::render('Admin/Riders/Edit', [
            'rider' => $this->toRiderArray($rider),
            'approvalOptions' => ['approved', 'rejected'],
        ]);
    }

    public function approve(ApproveRiderRequest $request, RiderProfile $rider): RedirectResponse
    {
        $validated = $request->validated();
        $approved = $validated['approval_status'] === 'approved';

        $rider->update([
            'approval_status' => $validated['approval_status'],
            'approved_at' => $approved ? now() : null,
            'approved_by' => $request->user()->id,
            'is_online' => $approved ? $rider->is_online : false,
        ]);

        $this->activityLogger->log(
            event: 'rider.approval_updated',
            subject: $rider,
            userId: $request->user()->id,
            metadata: [
                'approval_status' => $validated['approval_status'],
                'note' => $validated['note'] ?? null,
            ],
            request: $request
        );

        event(new RiderStatusUpdated($rider->fresh()));

        return to_route('admin.riders.show', $rider)->with('success', 'Rider approval updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function toRiderArray(RiderProfile $profile, bool $withDocuments = false): array
    {
        $payload = [
            'id' => $profile->id,
            'approval_status' => $profile->approval_status,
            'is_online' => $profile->is_online,
            'acceptance_rate' => (float) $profile->acceptance_rate,
            'cancellation_rate' => (float) $profile->cancellation_rate,
            'completed_deliveries' => $profile->completed_deliveries,
            'vehicle_type' => $profile->vehicle_type,
            'bicycle_model' => $profile->bicycle_model,
            'license_number' => $profile->license_number,
            'emergency_contact_name' => $profile->emergency_contact_name,
            'emergency_contact_phone' => $profile->emergency_contact_phone,
            'user' => [
                'id' => $profile->user->id,
                'name' => $profile->user->name,
                'email' => $profile->user->email,
                'phone' => $profile->user->phone,
            ],
            'zones' => $profile->zones->map(fn ($zone): array => [
                'id' => $zone->id,
                'name' => $zone->name,
            ])->values(),
            'approved_at' => optional($profile->approved_at)?->toIso8601String(),
            'created_at' => optional($profile->created_at)?->toIso8601String(),
        ];

        if ($withDocuments && $profile->relationLoaded('documents')) {
            $payload['documents'] = $profile->documents->map(fn ($document): array => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'document_url' => $document->document_url,
                'status' => $document->verified_at ? 'verified' : 'pending',
                'verified_at' => optional($document->verified_at)?->toIso8601String(),
            ])->values();
        }

        return $payload;
    }
}
