<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DBAssets\MediaAsset;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientLogoController extends Controller
{
    public function update(Request $request, string $clientId): RedirectResponse
    {
        $user = $request->user();
        $isInternal = empty($user->client_id);

        if (!$isInternal && $user->client_id !== $clientId) {
            abort(403, 'Tenant isolation violation.');
        }

        if (!in_array((string) $user->role?->name, ['MASTER', 'GESTOR', 'ADMIN_CLIENTE'], true)) {
            abort(403, 'Sem permissão para atualizar logo.');
        }

        $payload = $request->validate([
            'logo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:3072'],
        ]);

        $file = $payload['logo'];
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $fileName = Str::uuid().'.'.$ext;
        $path = 'logos/'.$clientId.'/'.$fileName;

        $stream = fopen($file->getRealPath(), 'rb');
        Storage::disk('local')->put($path, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        MediaAsset::query()
            ->where('owner_type', 'client')
            ->where('owner_id', $clientId)
            ->where('asset_type', 'logo')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        MediaAsset::create([
            'owner_type' => 'client',
            'owner_id' => $clientId,
            'asset_type' => 'logo',
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size_bytes' => (int) $file->getSize(),
            'storage_path' => $path,
            'checksum_sha256' => hash_file('sha256', $file->getRealPath()),
            'is_active' => true,
        ]);

        AuditLogger::log('CLIENT_LOGO_UPDATED', 'client', $clientId, null, ['logo' => 'updated'], $clientId);

        return back()->with('status', 'Logo atualizado com sucesso.');
    }

    public function show(Request $request, string $clientId): StreamedResponse
    {
        $user = $request->user();
        $isInternal = $user && empty($user->client_id);

        if (!$isInternal && $user?->client_id !== $clientId) {
            abort(403, 'Tenant isolation violation.');
        }

        $asset = MediaAsset::query()
            ->where('owner_type', 'client')
            ->where('owner_id', $clientId)
            ->where('asset_type', 'logo')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (!$asset || !Storage::disk('local')->exists($asset->storage_path)) {
            abort(404);
        }

        return response()->stream(function () use ($asset): void {
            echo Storage::disk('local')->get($asset->storage_path);
        }, 200, [
            'Content-Type' => $asset->mime_type,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
