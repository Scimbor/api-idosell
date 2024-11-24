<?php

namespace Api\Idosell\Commands;

use Illuminate\Console\Command;
use Api\Idosell\Facades\IdosellApi;
use Illuminate\Support\Facades\Storage;

class TestApiIdosellPackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:test-api-idosell-package';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Api Idosell package';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        dump('Command Started: '.$this->signature);

        $r = IdosellApi::request('products/products/get', [
            'params' => [
                'returnElements' => [
                    'vat',
                ],
            ],
        ])->post();

        dump('Dane z zapytania');
        Storage::put('products.json', json_encode($r));

        dump('Command Finished: '.$this->signature);
    }
}
