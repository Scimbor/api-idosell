<?php

namespace Api\Idosell;

use Api\Idosell\Exceptions\IdosellApiException;
use Illuminate\Support\Collection;

use Api\Idosell\Request;
use Api\Idosell\Response;

class IdosellApiService
{
    private const DEFAULT_RESULTS_LIMIT = 100;
    private const DEFAULT_RESULTS_PAGE = 1;

    private ?Request $request = null;
    private ?string $url = null;
    private array $params = [];
    private ?string $method = null;
    private ?Connection $connection = null;
    public ?object $results = null;

    private static array $instances = [];
    
    public function __construct(?string $connection = null)
    {
        $this->connection = new Connection($connection);
    }

    /**
     * Get singleton instance for specific connection
     *
     * @param string|null $connection
     * @return static
     */
    public static function getInstance(?string $connection = null): self
    {
        $connectionKey = $connection ?? 'default';
        
        if (!isset(self::$instances[$connectionKey])) {
            self::$instances[$connectionKey] = new self($connection);
        }
        
        return self::$instances[$connectionKey];
    }

    /**
     * Create or get instance with specific connection
     *
     * @param string $connection
     * @return static
     */
    public static function connection(string $connection = 'default'): self
    {
        return self::getInstance($connection);
    }

    /**
     * Initialize a new request with default connection
     *
     * @param string $url
     * @return static
     */
    public static function request(string $url): self
    {
        return self::getInstance()->initRequest($url);
    }

    /**
     * Initialize a new request
     *
     * @param string $url
     * @return $this
     */
    private function initRequest(string $url): self
    {
        $this->request = new Request($this->connection->getConfig());
        $this->url = $url;

        return $this;
    }

    /**
     * Get current connection name
     *
     * @return string
     */
    public function getCurrentConnection(): string
    {
        return $this->connection->getConnection();
    }

    /**
     * Handle dynamic method calls to make API requests
     *
     * @param string $method
     * @param array $args
     * @return $this|object|null
     * @throws IdosellApiException
     */
    public function __call(string $method, array $args)
    {
        if (!isset($this->request)) {
            throw new IdosellApiException('Request not initialized. Call request() method first.');
        }

        $this->params = ($args[0] ?? []);
        $this->method = $method;

        $this->results = $this->request->doRequest($method, $this->url, $this->params);

        if (empty($this->results)) {
            return $this->results;
        }

        // Return confirmation responses directly
        if ($this->results->type === Response::RESPONSE_CONFIRMATION_TYPE) {
            return $this->results;
        }

        // Return single responses directly
        if ($this->results->type === Response::RESPONSE_SINGLE_TYPE) {
            return $this->results;
        }

        // For paginated responses, return $this to allow chaining
        if ($this->hasPagination()) {
            $this->updatePaginationParams();
        }

        return $this;
    }

    /**
     * Handle static method calls
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic(string $method, array $args)
    {
        return self::getInstance()->$method(...$args);
    }

    /**
     * Check if response has pagination
     *
     * @return bool
     */
    private function hasPagination(): bool
    {
        return (isset($this->results->resultsNumberPage) && isset($this->results->resultsNumberAll)) 
            || (isset($this->params['params']['resultsPage']) || isset($this->params['params']['results_page']));
    }

    /**
     * Update pagination parameters for next request
     */
    private function updatePaginationParams(): void
    {
        // Initialize params structure if not exists
        if (!isset($this->params['params'])) {
            $this->params['params'] = [];
        }

        // Get current pagination values
        $currentPage = $this->params['params']['resultsPage'] ?? self::DEFAULT_RESULTS_PAGE;
        $currentLimit = $this->params['params']['resultsLimit'] ?? self::DEFAULT_RESULTS_LIMIT;

        // Update page number for next request
        $this->params['params']['resultsPage'] = $currentPage + 1;
        
        // Preserve the original limit
        $this->params['params']['resultsLimit'] = $currentLimit;
    }

    /**
     * Check if there are more pages to fetch
     *
     * @return bool
     */
    private function hasMorePages(): bool
    {
        // If we don't have results array or it's empty, no more pages
        if (!isset($this->results->results) || !is_array($this->results->results) || empty($this->results->results)) {
            return false;
        }

        // Get current pagination state
        $currentPage = $this->params['params']['resultsPage'] ?? self::DEFAULT_RESULTS_PAGE;
        $limit = $this->params['params']['resultsLimit'] ?? self::DEFAULT_RESULTS_LIMIT;
        $currentResults = count($this->results->results);

        // If we have total results count, use it to check if we should continue
        if (isset($this->results->resultsNumberAll)) {
            $processedResults = ($currentPage - 1) * $limit + $currentResults;
            return $processedResults < $this->results->resultsNumberAll;
        }

        // If we don't have total count, check if we got a full page
        return $currentResults >= $limit;
    }

    /**
     * Iterate through paginated results
     *
     * @param callable $callback
     * @return void
     * @throws IdosellApiException
     */
    public function each(callable $callback): void
    {
        if (!isset($this->results)) {
            throw new IdosellApiException('No results to iterate over. Make a request first.');
        }

        $processedResults = 0;
        $maxResults = 10000; // Safety limit

        do {
            // Check if we have valid results
            if (!isset($this->results->results) || !is_array($this->results->results)) {
                break;
            }

            // Check if the page is empty
            if (empty($this->results->results)) {
                break;
            }

            // Process current page results
            Collection::make($this->results->results)->each(function($item) use ($callback, &$processedResults) {
                $callback($item);
                $processedResults++;
            });

            // Safety check for maximum results
            if ($processedResults >= $maxResults) {
                break;
            }

            // Check if we should continue
            if (!$this->hasPagination() || !$this->hasMorePages()) {
                break;
            }

            // Update pagination parameters and fetch next page
            $this->updatePaginationParams();
            $this->results = $this->request->doRequest($this->method, $this->url, $this->params);

            // If new page has no results, stop
            if (empty($this->results->results) || 
                !isset($this->results->results) || 
                !is_array($this->results->results)) {
                break;
            }

        } while (true);
    }

    /**
     * Get response status for confirmation responses
     *
     * @return bool
     * @throws IdosellApiException
     */
    public function isSuccessful(): bool
    {
        if (!isset($this->results)) {
            throw new IdosellApiException('No results available. Make a request first.');
        }

        if ($this->results->type !== Response::RESPONSE_CONFIRMATION_TYPE) {
            throw new IdosellApiException('This method is only available for confirmation responses.');
        }

        return $this->results->status === true;
    }

    /**
     * Get response message for confirmation responses
     *
     * @return string
     * @throws IdosellApiException
     */
    public function getMessage(): string
    {
        if (!isset($this->results)) {
            throw new IdosellApiException('No results available. Make a request first.');
        }

        if ($this->results->type !== Response::RESPONSE_CONFIRMATION_TYPE) {
            throw new IdosellApiException('This method is only available for confirmation responses.');
        }

        return $this->results->message ?? '';
    }

    /**
     * Get all results as collection
     *
     * @return Collection
     * @throws IdosellApiException
     */
    public function get(): Collection
    {
        if (!isset($this->results)) {
            throw new IdosellApiException('No results to get. Make a request first.');
        }

        if ($this->results->type === Response::RESPONSE_CONFIRMATION_TYPE) {
            throw new IdosellApiException('This response contains only confirmation status. Use isSuccessful() to check the status.');
        }

        return Collection::make($this->results->results ?? []);
    }

    /**
     * Get first result
     *
     * @return mixed|null
     * @throws IdosellApiException
     */
    public function first()
    {
        return $this->get()->first();
    }
}
