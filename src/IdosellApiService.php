<?php

namespace Api\Idosell;

use GuzzleHttp\Client;
use Exception;

class IdosellApiService
{
    private const API_ENDPOINT = '/api/admin/v3/';
    private const DEFAULT_REQUEST_METHOD = 'get';
    private const ALLOWED_REQUEST_METHODS = [
        'post',
        'get',
        'put',
    ];

    private $config;
    private $client;
    private $url;
    private $params;
    
    public function checkService()
    {
        return true;
    }

    public function __construct()
    {
        $this->config = (object) config('idosell');

        if (empty($this->config->api_key) || empty($this->config->domain_url)) {
            throw new Exception("No data to connect with Idosell API");
        }

        $this->config->api_key = trim($this->config->api_key);
        $this->config->domain_url = trim($this->config->domain_url);

        if (empty($this->client)) {
            $this->client = new Client([
                // Base URI is used with relative requests
                'base_uri' => 'https://'.$this->config->domain_url.self::API_ENDPOINT,
                // You can set any number of default request options.
                'timeout'  => 180.0,
                'headers' => [
                    'X-API-KEY' => $this->config->api_key,
                ],
            ]);
        }
    }

    public function request(string $url, array $params = [])
    {
        $this->url = $url;
        $this->params = $params;

        return $this;
    }

    public function __call($method = self::DEFAULT_REQUEST_METHOD, $args)
    {
        // Sprawdzamy, czy metoda jest jedną z dozwolonych
        if (!in_array($method, self::ALLOWED_REQUEST_METHODS)) {
            throw new \BadMethodCallException("Metoda $method nie istnieje.");
        }

        $this->params = (($method === self::DEFAULT_REQUEST_METHOD) ? ['query' => $this->params] : ['json' => $this->params]);

        $response = $this->client->request($method, $this->url, $this->params);
        $response = $response->getBody();

        // Wywołanie odpowiedniej metody HTTP z URL i parametrami
        return json_decode($response->getContents());
    }
}
