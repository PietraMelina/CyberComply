<?php

namespace App\Services\Cmd;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RealCmdService implements CmdServiceInterface
{
    public function getAuthorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => config('services.cmd.client_id'),
            'redirect_uri' => config('services.cmd.redirect'),
            'response_type' => 'code',
            'scope' => implode(' ', config('services.cmd.scopes', ['openid', 'profile', 'attributes'])),
            'state' => $state,
            'nonce' => Str::random(40),
        ]);

        return config('services.cmd.authorization_endpoint').'?'.$query;
    }

    public function resolveIdentity(Request $request): array
    {
        $code = (string) $request->input('code');
        if ($code === '') {
            abort(422, 'Missing CMD code.');
        }

        $tokenResponse = Http::asForm()->post(config('services.cmd.token_endpoint'), [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.cmd.client_id'),
            'client_secret' => config('services.cmd.client_secret'),
            'redirect_uri' => config('services.cmd.redirect'),
            'code' => $code,
        ])->throw();

        $accessToken = (string) $tokenResponse->json('access_token');
        $userResponse = Http::withToken($accessToken)
            ->get(config('services.cmd.userinfo_endpoint'))
            ->throw();

        $nif = (string) ($userResponse->json('attributes.nic') ?? '');
        $name = (string) ($userResponse->json('name') ?? '');

        if (!preg_match('/^\d{9}$/', $nif) || $name === '') {
            abort(422, 'CMD identity payload is invalid.');
        }

        return [
            'nif' => $nif,
            'name' => $name,
            'is_mock' => false,
        ];
    }
}

