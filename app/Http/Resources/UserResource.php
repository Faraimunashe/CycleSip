<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roles = $this->roles->pluck('name')->values()->all();
        $latestIdentity = $this->relationLoaded('identityDocuments')
            ? $this->identityDocuments->first()
            : null;
        $riderProfile = $this->relationLoaded('riderProfile') ? $this->riderProfile : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => optional($this->date_of_birth)?->format('Y-m-d'),
            'status' => $this->status,
            'email_verified' => $this->hasVerifiedEmail(),
            'email_verified_at' => optional($this->email_verified_at)?->toIso8601String(),
            'age_verified' => $this->isAgeVerified(),
            'roles' => $roles,
            'has_rider_role' => in_array('rider', $roles, true),
            'can_use_rider_mode' => in_array('rider', $roles, true),
            'selected_delivery_address_id' => $this->selected_delivery_address_id,
            'address_selection_required' => in_array('customer', $roles, true)
                && $this->hasVerifiedEmail()
                && $this->selected_delivery_address_id === null,
            'identity' => $latestIdentity ? [
                'status' => $latestIdentity->status,
                'document_type' => $latestIdentity->document_type,
                'rejection_reason' => $latestIdentity->rejection_reason,
                'file_url' => $latestIdentity->file_url,
                'submitted_at' => optional($latestIdentity->created_at)?->toIso8601String(),
            ] : null,
            'rider_profile' => $riderProfile ? [
                'id' => $riderProfile->id,
                'approval_status' => $riderProfile->approval_status,
                'is_online' => (bool) $riderProfile->is_online,
            ] : null,
        ];
    }
}
