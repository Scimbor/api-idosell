<?php

namespace Api\Idosell\Facades;

use Illuminate\Support\Facades\Facade;

class IdosellApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Api\Idosell\IdosellApiService';
    }
}