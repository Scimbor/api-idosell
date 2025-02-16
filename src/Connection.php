<?php

namespace Api\Idosell;

use Exception;

class Connection
{
    private $config;

    public function __construct()
    {
        $this->config = (object) config('idosell')['default'];

        if (empty($this->config->api_key) || empty($this->config->domain_url)) {
            throw new Exception('No data to connect with Idosell API');
        }
        
        $this->config->api_key = trim($this->config->api_key);
        $this->config->domain_url = trim($this->config->domain_url);
    }

    public function getConfig(): object
    {
        return $this->config;
    }
}