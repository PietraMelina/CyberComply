<?php

namespace App\Services\Cmd;

use Illuminate\Http\Request;

interface CmdServiceInterface
{
    public function getAuthorizationUrl(string $state): string;

    /**
     * @return array{nif:string,name:string,is_mock:bool}
     */
    public function resolveIdentity(Request $request): array;
}

