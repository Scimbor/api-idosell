<?php

namespace Api\Idosell;

use Api\Idosell\Exceptions\IdosellApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response as HttpResponse;

class Request
{
    private const DEFAULT_PAGE_LIMIT = 100;
    private const TIMEOUT = 180;
    private const RETRY = 3;
    private const API_ENDPOINT = '/api/admin/v5/';
    private const ALLOWED_REQUEST_METHODS = [
        'post',
        'get',
        'put',
        'delete',
        'patch'
    ];

    private $http;
    private $config;
    private $response;

    public function __construct($config)
    {
        $this->config = $config;
        $this->initializeHttp();
        $this->response = new Response();
    }

    /**
     * Initialize HTTP client with default configuration
     */
    private function initializeHttp(): void
    {
        $this->http = Http::withHeaders([
            'X-API-KEY' => $this->config->api_key,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
        ->timeout(self::TIMEOUT)
        ->retry(self::RETRY, 100); // Retry with 100ms delay between attempts
    }

    /**
     * Execute API request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @return object
     * @throws IdosellApiException
     */
    public function doRequest(string $method, string $endpoint, array $params = []): object
    {
        if (!in_array(strtolower($method), self::ALLOWED_REQUEST_METHODS)) {
            throw new IdosellApiException("Method $method is not allowed.");
        }

        // Add pagination parameters if not set
        if (!isset($params['resultsLimit']) && !isset($params['results_limit'])) {
            $params['resultsLimit'] = self::DEFAULT_PAGE_LIMIT;
            $params['results_limit'] = self::DEFAULT_PAGE_LIMIT;
        }

        try {
            $response = $this->executeRequest($method, $endpoint, $params);
            
            if (!$response->successful()) {
                throw new IdosellApiException(
                    $response->body(),
                    $response->status()
                );
            }

            return $this->response->prepare($response->object());
        } catch (\Exception $e) {
            if ($e instanceof IdosellApiException) {
                throw $e;
            }
            
            throw new IdosellApiException(
                "API request failed: " . $e->getMessage(),
                $e->getCode() ?: 500
            );
        }
    }

    /**
     * Execute HTTP request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @return HttpResponse
     */
    private function executeRequest(string $method, string $endpoint, array $params): HttpResponse
    {
        $url = 'https://' . $this->config->domain_url . self::API_ENDPOINT . $endpoint;
        
        return $this->http->{strtolower($method)}($url, $params);
    }
}