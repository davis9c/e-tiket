<?php

namespace App\Services;

use Hashids\Hashids;

class HashIdService
{
    protected Hashids $hashids;

    public function __construct()
    {
        $salt   = env('HASHID_SALT', 'default-salt');
        $length = (int) env('HASHID_LENGTH', 8);

        $this->hashids = new Hashids($salt, $length);
    }

    public function encode($id): string
    {
        return $this->hashids->encode($id);
    }

    public function decode(?string $hash): ?int
    {
        if (empty($hash)) {
            return null;
        }

        $decoded = $this->hashids->decode($hash);
        return isset($decoded[0]) ? (int) $decoded[0] : null;
    }
}
