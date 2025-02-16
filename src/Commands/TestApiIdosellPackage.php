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

        // Endpoint with resultsPages
        IdosellApi::connection('default')->request('products/products/get')->post([
            'params' => [
                // 'resultsLimit' => 5,
                'returnElements' => [
                    'vat',
                ],
            ],
        ])->each(function($product) {
            dump('Produkt:'. $product->productId);
        });

        IdosellApi::request('returns/returns')->get([
            'results_limit' => 1,
            'range' => [
                'date' => [
                    'date_begin' => '2002-01-01',
                    'date_end' => date('Y-m-d'),
                    'dates_type' => 'date_end',
                ],
            ],
        ])->each(function($return) {
            dump($return);
        });

        $r = IdosellApi::request('refunds/retrieveRefundsList')->get([
            'sourceType' => 'rma',
        ]);
        dump('Dane', $r);

        // Example with adding data to API
        $r = IdosellApi::request('clients/clients')->post([
            'settings' => [
                'send_mail' => false,
                'send_sms' => false,
            ],
            'params' => [
                'clients' => [
                    [
                        'code_extern' => 'test_account_'.time(),
                        'email' => 'test_email@'.time().'.com',
                        'firstname' => 'Testowe_imie',
                        'lastname' => 'Testowe_nazwisko',
                        'street' => 'Wojska Polskiego 100/2a',
                        'zipcode' => '73-100',
                        'city' => 'Szczecin',
                        'country_code' => 'pl',
                        'phone' => '111111111',
                        'wholesaler' => false,
                        'language' => 'pol',
                        'shops' => [
                            1,
                        ],
                        'sms_newsletter' => false,
                        'email_newsletter' => true,
                    ]
                ],
            ],
        ]);

        dump('Dodawanie klienta do bazy: ',$r);

        // Example with updating data in API
        $r = IdosellApi::request('clients/clients')->put([
            'clientsSettings' => [
                'clientSettingSendMail' => false,
                'clientSettingSendSms' => false,
            ],
            'params' => [
                'clients' => [
                    [
                        'clientLogin' => 'test_email@1739464172.com',
                        'clientNote' => 'Updated at '.date('Y-m-d H:i:s'),
                    ]
                ],
            ],
        ]);

        dump('Aktualizacja klienta w bazie: ',$r);

        dump('Command Finished: '.$this->signature);
    }
}
