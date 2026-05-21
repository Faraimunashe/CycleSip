<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIdentityDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerManagementController extends Controller
{
    public function index(): Response
    {
        $customers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'customer'))
            ->with(['identityDocuments' => fn ($query) => $query->latest()->limit(1)])
            ->withCount('orders')
            ->latest()
            ->paginate(12)
            ->through(fn (User $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'date_of_birth' => optional($customer->date_of_birth)?->format('Y-m-d'),
                'email_verified_at' => optional($customer->email_verified_at)?->toIso8601String(),
                'age_verified_at' => optional($customer->age_verified_at)?->toIso8601String(),
                'status' => $customer->status,
                'orders_count' => $customer->orders_count,
                'identity_document' => $customer->identityDocuments->first()
                    ? [
                        'id' => $customer->identityDocuments->first()->id,
                        'document_type' => $customer->identityDocuments->first()->document_type,
                        'status' => $customer->identityDocuments->first()->status,
                        'file_url' => $customer->identityDocuments->first()->file_url,
                        'rejection_reason' => $customer->identityDocuments->first()->rejection_reason,
                    ]
                    : null,
            ]);

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function reviewIdentity(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->hasRole('customer'), 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                UserIdentityDocument::STATUS_APPROVED,
                UserIdentityDocument::STATUS_REJECTED,
            ])],
            'rejection_reason' => ['nullable', 'string', 'max:255', 'required_if:status,rejected'],
        ]);

        $document = $customer->identityDocuments()
            ->where('status', UserIdentityDocument::STATUS_PENDING)
            ->latest()
            ->first();

        if (! $document) {
            return back()->withErrors(['identity' => 'No pending identity document found for this customer.']);
        }

        $document->update([
            'status' => $validated['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['status'] === UserIdentityDocument::STATUS_REJECTED
                ? ($validated['rejection_reason'] ?? null)
                : null,
        ]);

        if ($validated['status'] === UserIdentityDocument::STATUS_APPROVED && $customer->age_verified_at === null) {
            $customer->update(['age_verified_at' => now()]);
        }

        return back()->with('success', 'Identity document review saved.');
    }
}
