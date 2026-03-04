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

    public function decode(string $hash): ?int
    {
        $decoded = $this->hashids->decode($hash);
        return $decoded[0] ?? null;
    }
}
