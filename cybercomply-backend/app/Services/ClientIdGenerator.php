<?php

namespace App\Services;

class ClientIdGenerator
{
    public function generate(string $type, int $year): string
    {
        $random = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));

        return sprintf('%s-%d-%s', strtoupper($type), $year, $random);
    }
}
