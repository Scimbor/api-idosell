<?php

namespace Api\Idosell;

use Exception;

use Api\Idosell\Request;

class IdosellApiService
{
    private $request;
    private $config;
    private $url;
    private $params;
    private $method;
    private $results;
    
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

        $this->request = new Request($this->config);
    }

    public function request(string $url)
    {
        $this->url = $url;

        return $this;
    }

    public function __call($method, $args)
    {
        $this->params = ($args[0] ?? []);
        $this->method = $method;
        $this->results = $this->request->doRequest($method, $this->url, $this->params);

        if (!isset($this->results->resultsNumberPage) && !isset($this->results->resultsNumberAll)) { 
            return $this->results;
        }

        return $this;
    }

    public function each(callable $callback)
    {
        if (!isset($this->results->resultsNumberPage) && !isset($this->results->resultsNumberAll)) {
            return;
        }

        collect($this->results->results)->each(function($item) use (&$callback) {
            $callback($item);
        });

        $this->params['params']['resultsPage'] = $this->results->resultsPage + 1;
        $this->params['params']['results_page'] = $this->results->resultsPage + 1;

        if ($this->params['params']['resultsPage'] == $this->results->resultsNumberPage) {
            return;
        }

        $this->results = $this->request->doRequest($this->method, $this->url, $this->params);
        $this->each($callback);
    }
}
