<?php

namespace App\Services;

class UserIdGenerator
{
    public function generate(string $companyCode): string
    {
        $random = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));

        return sprintf('%s-%s', strtoupper($companyCode), $random);
    }
}
