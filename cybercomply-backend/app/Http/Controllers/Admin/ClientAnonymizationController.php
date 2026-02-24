<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DB1\Client;
use App\Models\DB1\ClientAddress;
use App\Models\DB1\Site;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientAnonymizationController extends Controller
{
    public function anonymize(Request $request, string $id): JsonResponse
    {
        $payload = $request->validate([
            'confirm_text' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $expected = 'ANONYMIZE '.$id;
        if ($payload['confirm_text'] !== $expected) {
            return response()->json([
                'message' => 'Confirmation text mismatch.',
                'expected' => $expected,
            ], 422);
        }

        $client = Client::query()->findOrFail($id);
        $users = User::query()->where('client_id', $id)->get();
        $addresses = ClientAddress::query()->where('client_id', $id)->get();
        $sites = Site::query()->where('client_id', $id)->get();

        DB::connection('db1')->transaction(function () use ($request, $client, $users, $addresses, $sites): void {
            $clientHash = $this->hashValue($client->id, 'client');

            $client->update([
                'name' => 'ANONYMIZED-'.$clientHash,
                'vat_number' => 'ANONYMIZED-'.$clientHash,
                'representative_nif' => null,
                'representative_name' => 'ANONYMIZED',
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivated_by' => (string) $request->user()->id,
            ]);

            foreach ($users as $user) {
                $email = 'anon.'.$this->hashValue($user->id, 'user').'@anon.invalid';
                $user->update([
                    'email' => $email,
                    'nif' => null,
                    'is_active' => false,
                    'mfa_enabled' => false,
                    'mfa_secret' => null,
                    'mfa_backup_codes' => null,
                    'email_verified_at' => null,
                    'last_login_at' => null,
                ]);
            }

            foreach ($addresses as $address) {
                $address->update([
                    'address_line1' => 'ANONYMIZED',
                    'address_line2' => null,
                    'city' => 'ANONYMIZED',
                    'postal_code' => '0000-000',
                ]);
            }

            foreach ($sites as $site) {
                $site->update([
                    'name' => 'ANONYMIZED-SITE-'.$site->id,
                    'is_active' => false,
                ]);
            }
        });

        $responsesMasked = DB::connection('db2')
            ->table('responses')
            ->where('client_id', $id)
            ->whereNotNull('comment')
            ->update(['comment' => '[ANONYMIZED]']);

        $evidencesMasked = DB::connection('db2')
            ->table('evidences')
            ->where('client_id', $id)
            ->update(['original_filename' => DB::raw("CONCAT('ANONYMIZED_', id)")]);

        AuditLogger::log(
            'CLIENT_ANONYMIZED',
            'client',
            $client->id,
            null,
            [
                'client_id' => $client->id,
                'users_anonymized' => $users->count(),
                'addresses_anonymized' => $addresses->count(),
                'sites_anonymized' => $sites->count(),
                'responses_masked' => $responsesMasked,
                'evidences_masked' => $evidencesMasked,
                'reason' => $payload['reason'] ?? null,
            ],
            $client->id
        );

        return response()->json([
            'message' => 'Client anonymized successfully.',
            'client_id' => $client->id,
            'users_anonymized' => $users->count(),
            'addresses_anonymized' => $addresses->count(),
            'sites_anonymized' => $sites->count(),
            'responses_masked' => $responsesMasked,
            'evidences_masked' => $evidencesMasked,
        ]);
    }

    private function hashValue(string $value, string $scope): string
    {
        $key = (string) config('app.key', 'fallback-key');
        $hash = hash_hmac('sha256', $scope.'|'.$value, $key);

        return substr($hash, 0, 12);
    }
}
