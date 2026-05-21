<?php

namespace App\Http\Controllers;

use App\Models\UserIdentityDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class IdentityVerificationController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        $latestDocument = $user->identityDocuments()->latest()->first();

        return Inertia::render('Compliance/IdentityUpload', [
            'documentTypes' => UserIdentityDocument::DOCUMENT_TYPES,
            'latestDocument' => $latestDocument ? [
                'id' => $latestDocument->id,
                'document_type' => $latestDocument->document_type,
                'status' => $latestDocument->status,
                'rejection_reason' => $latestDocument->rejection_reason,
                'file_url' => $latestDocument->file_url,
                'created_at' => optional($latestDocument->created_at)?->toIso8601String(),
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', Rule::in(UserIdentityDocument::DOCUMENT_TYPES)],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $user = $request->user();
        $path = $request->file('document')->store('identity-documents', 'public');

        UserIdentityDocument::query()->create([
            'user_id' => $user->id,
            'document_type' => $validated['document_type'],
            'file_url' => Storage::url($path),
            'status' => UserIdentityDocument::STATUS_PENDING,
        ]);

        return back()->with('success', 'Identity document submitted for review.');
    }
}
