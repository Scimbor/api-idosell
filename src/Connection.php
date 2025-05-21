<?php

namespace Api\Idosell;

use Api\Idosell\Exceptions\IdosellApiException;

class Connection
{
    private const CONFIG_FILE = 'idosell.php';
    private object $config;
    private string $connectionName = 'default';

    /**
     * @param string|null $connection
     * @throws IdosellApiException
     */
    public function __construct(?string $connection = null)
    {
        $this->loadConfig($connection);
        $this->validateConfig();
    }

    /**
     * Load configuration from file or Laravel config
     *
     * @param string|null $connection
     * @throws IdosellApiException
     */
    private function loadConfig(?string $connection = null): void
    {
        $fullConfig = $this->getFullConfig();

        // If no connection specified, use default from config or 'default'
        $this->connectionName = (empty($connection)) ? 'default' : $connection;

        // Check if connection exists
        if (!isset($fullConfig[$this->connectionName])) {
            $availableConnections = array_keys($fullConfig ?? []);
            throw new IdosellApiException(
                "Connection '{$this->connectionName}' not found in configuration. " .
                "Available connections: " . implode(', ', $availableConnections)
            );
        }

        // Convert connection config to object
        $connectionConfig = $fullConfig[$this->connectionName];
        $this->config = (object) [
            'api_key' => $connectionConfig['api_key'] ?? null,
            'domain_url' => $connectionConfig['domain_url'] ?? null
        ];
    }

    /**
     * Get full configuration array
     *
     * @return array
     */
    private function getFullConfig(): array
    {
        // Try to load from Laravel config first
        if (function_exists('config')) {
            $config = config('idosell');
            if (!empty($config) && is_array($config)) {
                return $config;
            }
        }

        // Try to load from file
        $configPath = $this->getConfigPath();
        if (file_exists($configPath)) {
            $fileConfig = require $configPath;
            if (is_array($fileConfig)) {
                return $fileConfig;
            }
        }

        // Return default config structure
        return [
            'default' => 'default',
            'connections' => [
                'default' => [
                    'api_key' => env('API_IDOSELL_KEY'),
                    'domain_url' => env('API_SHOP_DOMAIN'),
                ]
            ]
        ];
    }

    /**
     * Get configuration file path
     *
     * @return string
     */
    private function getConfigPath(): string
    {
        // Check Laravel config path
        if (function_exists('config_path')) {
            $laravelPath = config_path(self::CONFIG_FILE);
            if (file_exists($laravelPath)) {
                return $laravelPath;
            }
        }

        // Check package config
        $packagePath = __DIR__ . '/config/' . self::CONFIG_FILE;
        if (file_exists($packagePath)) {
            return $packagePath;
        }

        // Return default path as fallback
        return __DIR__ . '/config/' . self::CONFIG_FILE;
    }

    /**
     * Validate configuration
     *
     * @throws IdosellApiException
     */
    private function validateConfig(): void
    {
        $requiredFields = ['api_key', 'domain_url'];

        foreach ($requiredFields as $field) {
            if (empty($this->config->$field)) {
                throw new IdosellApiException(
                    "Missing required configuration field: {$field} for connection '{$this->connectionName}'. " .
                    "Please check your configuration in config/idosell.php or .env file"
                );
            }
        }

        // Clean up the values
        $this->config->api_key = trim($this->config->api_key);
        $this->config->domain_url = trim($this->config->domain_url);
        $this->config->domain_url = rtrim($this->config->domain_url, '/');
    }

    /**
     * Get configuration object
     *
     * @return object
     */
    public function getConfig(): object
    {
        return $this->config;
    }

    /**
     * Get current connection name
     *
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connectionName;
    }
}