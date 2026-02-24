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

class AvatarController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'avatar' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $file = $payload['avatar'];
        $disk = Storage::disk('local');

        $ext = strtolower((string) $file->getClientOriginalExtension());
        $fileName = Str::uuid().'.'.$ext;
        $path = 'avatars/'.$user->id.'/'.$fileName;

        $stream = fopen($file->getRealPath(), 'rb');
        $disk->put($path, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        $checksum = hash_file('sha256', $file->getRealPath());

        $before = $user->toArray();
        $existingAssetId = $user->avatar_asset_id;
        if ($existingAssetId) {
            $existing = MediaAsset::query()->find($existingAssetId);
            if ($existing) {
                $existing->is_active = false;
                $existing->save();
            }
        }

        $asset = MediaAsset::create([
            'owner_type' => 'user',
            'owner_id' => $user->id,
            'asset_type' => 'avatar',
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size_bytes' => (int) $file->getSize(),
            'storage_path' => $path,
            'checksum_sha256' => $checksum,
            'is_active' => true,
        ]);

        $user->avatar_asset_id = $asset->id;
        $user->save();

        AuditLogger::log('USER_AVATAR_UPDATED', 'user', $user->id, $before, $user->fresh()->toArray(), $user->client_id);

        return back()->with('status', 'Avatar atualizado com sucesso.');
    }

    public function show(Request $request, int $id): StreamedResponse
    {
        $asset = MediaAsset::query()->findOrFail($id);

        if (!$asset->is_active || $asset->asset_type !== 'avatar') {
            abort(404);
        }

        $user = $request->user();
        $isOwner = $user && $asset->owner_type === 'user' && $asset->owner_id === $user->id;
        $isInternalViewer = $user && empty($user->client_id);

        if (!$isOwner && !$isInternalViewer) {
            abort(403);
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($asset->storage_path)) {
            abort(404);
        }

        return response()->stream(function () use ($disk, $asset): void {
            echo $disk->get($asset->storage_path);
        }, 200, [
            'Content-Type' => $asset->mime_type,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
