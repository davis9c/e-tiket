<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use Hashids\Hashids;

class Services extends BaseService
{
    public static function hashids($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('hashids');
        }

        return new Hashids('eticket_salt_rahasia', 16);
    }
}
