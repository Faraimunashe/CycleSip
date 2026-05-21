<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\UserIdentityDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class IdentityController extends Controller
{
    use RespondsWithJson;

    public function show(Request $request): JsonResponse
    {
        $latestDocument = $request->user()->identityDocuments()->latest()->first();

        return $this->ok([
            'document_types' => UserIdentityDocument::DOCUMENT_TYPES,
            'latest_document' => $latestDocument ? [
                'id' => $latestDocument->id,
                'document_type' => $latestDocument->document_type,
                'status' => $latestDocument->status,
                'rejection_reason' => $latestDocument->rejection_reason,
                'file_url' => $latestDocument->file_url,
                'created_at' => optional($latestDocument->created_at)?->toIso8601String(),
            ] : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', Rule::in(UserIdentityDocument::DOCUMENT_TYPES)],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = $request->file('document')->store('identity-documents', 'public');

        $document = $request->user()->identityDocuments()->create([
            'document_type' => $validated['document_type'],
            'file_url' => Storage::url($path),
            'status' => UserIdentityDocument::STATUS_PENDING,
        ]);

        return $this->created([
            'document' => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'status' => $document->status,
                'file_url' => $document->file_url,
                'created_at' => optional($document->created_at)?->toIso8601String(),
            ],
        ], 'Identity document submitted for review.');
    }
}
