<?php

namespace App\Services\Cmd;

use Illuminate\Http\Request;

class MockCmdService implements CmdServiceInterface
{
    public function getAuthorizationUrl(string $state): string
    {
        return route('cmd.callback', [
            'state' => $state,
            'mock' => 1,
        ]);
    }

    public function resolveIdentity(Request $request): array
    {
        return [
            'nif' => (string) config('services.cmd.mock_nif', '123456789'),
            'name' => (string) config('services.cmd.mock_name', 'Utilizador CMD'),
            'is_mock' => true,
        ];
    }
}

