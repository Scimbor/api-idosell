<?php

namespace Api\Idosell;

class Response
{
    public const RESPONSE_SINGLE_TYPE = 'single';
    public const RESPONSE_PAGED_TYPE = 'paged';

    private const MAPPED_RESPONSE_PROPERTIES = [
        'results_number_page' => 'resultsNumberPage',
        'results_number_all'  => 'resultsNumberAll',
        'results_page' => 'resultsPage',
        'results_limit' => 'resultsLimit',
        'returns' => 'results',
        'documents' => 'results',
        'Results' => 'results',
    ];

    public function prepare($object)
    {
        if (empty($object)) {
            return (object) $object;
        }

        foreach (self::MAPPED_RESPONSE_PROPERTIES as $oldProperty => $newProperty) {
            if (!isset($object->$oldProperty)) {
                continue;
            }

            $object->$newProperty = $object->$oldProperty;
            unset($object->$oldProperty);
        }

        $object->type = (isset($object->resultsPage) ? self::RESPONSE_PAGED_TYPE : self::RESPONSE_SINGLE_TYPE);
    
        return $object;
    }
}