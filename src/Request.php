<?php

namespace Api\Idosell;

use Illuminate\Support\Facades\Http;

class Request
{
    private const DEFAULT_PAGE_LIMIT = 100;
    private const TIMEOUT = 180;
    private const RETRY = 3;
    private const API_ENDPOINT = '/api/admin/v4/';
    private const ALLOWED_REQUEST_METHODS = [
        'post',
        'get',
        'put',
    ];

    private $http;
    private $config;
    private $response;

    public function __construct($config)
    {
        $this->config = $config;

        if (empty($this->http)) {
            $this->http = Http::withHeaders([
                'X-API-KEY' => $this->config->api_key,
            ])->timeout(self::TIMEOUT)->retry(self::RETRY);
        }

        if (empty($this->response)) {
            $this->response = new Response();
        }
    }

    public function doRequest($method, $endpoint, $params = [])
    {
        if (!in_array($method, self::ALLOWED_REQUEST_METHODS)) {
            throw new \BadMethodCallException("Metoda $method nie istnieje.");
        }

        $params['resultsLimit'] = self::DEFAULT_PAGE_LIMIT;
        $params['results_limit'] = self::DEFAULT_PAGE_LIMIT;

        $response = $this->http->{$method}('https://'.$this->config->domain_url.self::API_ENDPOINT.$endpoint, $params);
        $response = $this->response->prepare($response->object());

        return $response;
    }
}