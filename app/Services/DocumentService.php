<?php

namespace App\Services;

use App\Contracts\Integrations\SignaturePort;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    public function __construct(private readonly SignaturePort $signature) {}

    /** @param list<array<string, mixed>> $participants */
    public function requestSignature(Document $document, array $participants, int $providerId): string
    {
        if ($document->status !== 'approved') {
            throw ValidationException::withMessages(['document' => 'Only an approved document can be signed.']);
        }
        $version = DB::table('document_versions')->where('document_id', $document->id)->where('version', $document->current_version)->first();
        if (! $version) {
            throw ValidationException::withMessages(['document' => 'The immutable current document version is missing.']);
        }
        $external = $this->signature->createRequest($version->id, $participants);
        $id = (string) Str::uuid();
        DB::table('signature_requests')->insert([
            'id' => $id, 'organization_id' => $document->organization_id, 'document_version_id' => $version->id,
            'provider_id' => $providerId, 'external_request_id' => $external['id'], 'status' => 'pending',
            'expires_at' => $external['expires_at'] ?? null, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }
}
