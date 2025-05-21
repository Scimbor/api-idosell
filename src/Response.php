<?php

namespace Api\Idosell;

use Api\Idosell\Exceptions\IdosellApiException;
use stdClass;

class Response
{
    public const RESPONSE_SINGLE_TYPE = 'single';
    public const RESPONSE_PAGED_TYPE = 'paged';
    public const RESPONSE_ERROR_TYPE = 'error';
    public const RESPONSE_CONFIRMATION_TYPE = 'confirmation';

    private const MAPPED_RESPONSE_PROPERTIES = [
        'results_number_page' => 'resultsNumberPage',
        'results_number_all'  => 'resultsNumberAll',
        'results_page' => 'resultsPage',
        'results_limit' => 'resultsLimit',
        'returns' => 'results',
        'documents' => 'results',
        'Results' => 'results',
        'status' => 'status',
        'success' => 'success'
    ];

    /**
     * Prepare and validate API response
     *
     * @param stdClass|array $response
     * @return stdClass
     * @throws IdosellApiException
     */
    public function prepare($response): stdClass
    {
        if (empty($response)) {
            return new stdClass();
        }

        $object = is_array($response) ? (object) $response : $response;

        // Check for API errors
        if (isset($object->error)) {
            throw new IdosellApiException(
                $object->error->message ?? 'Unknown API error',
                $object->error->code ?? 500,
                $object->error
            );
        }

        // Map response properties
        foreach (self::MAPPED_RESPONSE_PROPERTIES as $oldProperty => $newProperty) {
            if (!isset($object->$oldProperty)) {
                continue;
            }

            $object->$newProperty = $object->$oldProperty;
            unset($object->$oldProperty);
        }

        // Set response type and normalize response structure
        $object->type = $this->determineResponseType($object);
        $this->normalizeResponse($object);
        
        return $object;
    }

    /**
     * Determine the type of response
     *
     * @param stdClass $object
     * @return string
     */
    private function determineResponseType(stdClass $object): string
    {
        // Check for error response
        if (isset($object->error)) {
            return self::RESPONSE_ERROR_TYPE;
        }

        // Check for confirmation/status response
        if ($this->isConfirmationResponse($object)) {
            return self::RESPONSE_CONFIRMATION_TYPE;
        }

        // Check for paginated response
        if ($this->isPaginatedResponse($object)) {
            return self::RESPONSE_PAGED_TYPE;
        }

        // Default to single response
        return self::RESPONSE_SINGLE_TYPE;
    }

    /**
     * Check if response is a confirmation/status response
     *
     * @param stdClass $object
     * @return bool
     */
    private function isConfirmationResponse(stdClass $object): bool
    {
        // Check various confirmation indicators
        if (isset($object->status) && is_bool($object->status)) {
            return true;
        }

        if (isset($object->success) && is_bool($object->success)) {
            return true;
        }

        if (isset($object->confirmed) && is_bool($object->confirmed)) {
            return true;
        }

        // Check if response contains only status information
        $statusFields = ['status', 'success', 'message', 'code'];
        $objectVars = get_object_vars($object);
        $hasOnlyStatusFields = !empty($objectVars) && count(array_diff(array_keys($objectVars), $statusFields)) === 0;

        return $hasOnlyStatusFields;
    }

    /**
     * Check if response is paginated
     *
     * @param stdClass $object
     * @return bool
     */
    private function isPaginatedResponse(stdClass $object): bool
    {
        // Check for standard pagination fields
        if (isset($object->resultsPage) || isset($object->resultsNumberPage)) {
            return true;
        }

        // Check for results array with pagination metadata
        if (isset($object->results) && is_array($object->results)) {
            return isset($object->resultsNumberAll) || 
                   isset($object->resultsLimit) || 
                   isset($object->page) || 
                   isset($object->pages);
        }

        return false;
    }

    /**
     * Normalize response structure based on type
     *
     * @param stdClass $object
     */
    private function normalizeResponse(stdClass $object): void
    {
        switch ($object->type) {
            case self::RESPONSE_CONFIRMATION_TYPE:
                $this->normalizeConfirmationResponse($object);
                break;

            case self::RESPONSE_PAGED_TYPE:
                $this->normalizePaginatedResponse($object);
                break;

            case self::RESPONSE_SINGLE_TYPE:
                $this->normalizeSingleResponse($object);
                break;
        }
    }

    /**
     * Normalize confirmation response
     *
     * @param stdClass $object
     */
    private function normalizeConfirmationResponse(stdClass $object): void
    {
        // Ensure standard status field
        if (!isset($object->status)) {
            $object->status = $object->success ?? $object->confirmed ?? false;
        }

        // Ensure message field exists
        if (!isset($object->message)) {
            $object->message = '';
        }
    }

    /**
     * Normalize paginated response
     *
     * @param stdClass $object
     */
    private function normalizePaginatedResponse(stdClass $object): void
    {
        // Ensure results is always an array
        if (!isset($object->results)) {
            $object->results = [];
        }

        // Ensure standard pagination fields
        if (!isset($object->resultsPage)) {
            $object->resultsPage = $object->page ?? 1;
        }

        if (!isset($object->resultsNumberAll)) {
            $object->resultsNumberAll = $object->total ?? count($object->results);
        }

        if (!isset($object->resultsNumberPage)) {
            $object->resultsNumberPage = $object->pages ?? 1;
        }
    }

    /**
     * Normalize single response
     *
     * @param stdClass $object
     */
    private function normalizeSingleResponse(stdClass $object): void
    {
        // If response is a simple value or array, wrap it in a results field
        if (!isset($object->results) && !$this->isConfirmationResponse($object)) {
            $data = get_object_vars($object);
            unset($data['type']); // Remove type field from results
            $object->results = empty($data) ? null : $data;
        }
    }
}