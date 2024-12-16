<?php

namespace Api\Idosell;

use Exception;

use Api\Idosell\Request;

class IdosellApiService
{
    const DEFAULT_RESULTS_PAGE_LIMIT = 100;

    private $request;
    private $config;
    private $url;
    private $params;
    private $method;
    public $results;
    
    public function checkService()
    {
        return true;
    }

    public function __construct()
    {
        $this->config = (object) config('idosell')['default'];

        if (empty($this->config->api_key) || empty($this->config->domain_url)) {
            throw new Exception('No data to connect with Idosell API');
        }

        $this->config->api_key = trim($this->config->api_key);
        $this->config->domain_url = trim($this->config->domain_url);
    }

    public function connection($connection = '')
    {
        if (empty($connection)) {
            return $this;
        }

        $this->config = config('idosell');

        if (!isset($this->config[$connection])) {
            throw new Exception('Connection '.$connection.' does not exist');
        }

        $this->config = (object) $this->config[$connection];

        if (empty($this->config->api_key) || empty($this->config->domain_url)) {
            throw new Exception('No data to connect with Idosell API for '.$connection.' connection');
        }

        return $this;
    }

    public function request(string $url)
    {
        $this->request = new Request($this->config);
        $this->url = $url;

        return $this;
    }

    public function __call($method, $args)
    {
        $this->params = ($args[0] ?? []);
        $this->method = $method;

        $this->results = $this->request->doRequest($method, $this->url, $this->params);

        if ((!isset($this->results->resultsNumberPage) && !isset($this->results->resultsNumberAll)) && (!isset($this->params['params']['resultsPage']) && !isset($this->params['params']['results_page']))) { 
            return $this->results;
        }

        return $this;
    }

    public function each(callable $callback)
    {
        collect($this->results->results)->each(function($item) use (&$callback) {
            $callback($item);
        });

        // Sometimest API gates have params limits property but not return in response

        if ((isset($this->results->resultsNumberPage) && isset($this->results->resultsNumberAll)) || (isset($this->params['params']['resultsPage']) || isset($this->params['params']['results_page']))) {
            $this->params['params']['resultsPage'] = $this->results->resultsPage + 1;
            $this->params['params']['results_page'] = $this->results->resultsPage + 1;
        }

        if ($this->params['params']['resultsPage'] == $this->results->resultsNumberPage) {
            return;
        }

        $this->results = $this->request->doRequest($this->method, $this->url, $this->params);
        $this->each($callback);
    }
}
