<?php

namespace Api\Idosell\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Api\Idosell\IdosellApiService connection(string $connection = 'default')
 * @method static \Api\Idosell\IdosellApiService request(string $url)
 * @method static \Illuminate\Support\Collection get()
 * @method static mixed first()
 * @method static void each(callable $callback)
 * @method static \Api\Idosell\IdosellApiService post(array $params = [])
 * @method static \Api\Idosell\IdosellApiService get(array $params = [])
 * @method static \Api\Idosell\IdosellApiService put(array $params = [])
 * @method static \Api\Idosell\IdosellApiService delete(array $params = [])
 * @method static \Api\Idosell\IdosellApiService patch(array $params = [])
 */
class IdosellApi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'idosell-api';
    }
}