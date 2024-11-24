<?php

namespace Api\Idosell\Commands;

use Illuminate\Console\Command;
use Api\Idosell\Facades\IdosellApi;

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

        IdosellApi::checkService();

        dump('Command Finished: '.$this->signature);
    }
}
