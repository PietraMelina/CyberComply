<?php

namespace App\Services;

use App\Models\DB2\Evidence;
use App\Models\DB2\Response as TenantResponse;
use Illuminate\Http\UploadedFile;

class EvidenceStorage
{
    public function store(UploadedFile $file, TenantResponse $response, string $userId, string $clientId): Evidence
    {
        $allowedMime = ['application/pdf', 'image/jpeg', 'image/png'];
        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];

        $mime = (string) $file->getMimeType();
        $size = (int) $file->getSize();
        $ext = strtolower((string) $file->getClientOriginalExtension());

        if (!in_array($mime, $allowedMime, true)) {
            abort(422, 'Tipo de arquivo não permitido.');
        }

        if ($size > 5 * 1024 * 1024) {
            abort(422, 'Arquivo excede 5MB.');
        }

        if (!in_array($ext, $allowedExt, true)) {
            abort(422, 'Extensão não permitida.');
        }

        $internalToken = bin2hex(random_bytes(32));
        $basePath = (string) config('filesystems.evidence_storage_path', storage_path('evidences'));
        $dir = $this->ensureWritableDirectory($basePath, $clientId);

        $path = $dir.'/'.$internalToken;
        $file->move($dir, $internalToken);

        return Evidence::create([
            'client_id' => $clientId,
            'response_id' => $response->id,
            'internal_token' => $internalToken,
            'original_filename' => $file->getClientOriginalName(),
            'file_size_bytes' => $size,
            'mime_type' => $mime,
            'storage_path' => $path,
            'checksum_sha256' => hash_file('sha256', $path),
            'uploaded_by' => $userId,
            'uploaded_at' => now(),
        ]);
    }

    private function ensureWritableDirectory(string $configuredBasePath, string $clientId): string
    {
        $candidates = [
            rtrim($configuredBasePath, '/'),
            storage_path('evidences'),
        ];

        foreach ($candidates as $basePath) {
            $dir = rtrim($basePath, '/').'/'.$clientId;

            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                continue;
            }

            if (is_writable($dir)) {
                return $dir;
            }
        }

        abort(500, 'Unable to prepare writable evidence storage directory.');
    }
}
