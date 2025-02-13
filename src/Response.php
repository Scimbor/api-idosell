<?php

namespace Api\Idosell;

class Response
{
    private const MAPPED_RESPONSE_PROPERTIES = [
        'results_number_page' => 'resultsNumberPage',
        'results_number_all'  => 'resultsNumberAll',
        'results_page' => 'resultsPage',
        'results_limit' => 'resultsLimit',
        'returns' => 'results',
        'documents' => 'results',
    ];

    public function prepare($object)
    {
        foreach (self::MAPPED_RESPONSE_PROPERTIES as $oldProperty => $newProperty) {
            if (!isset($object->$oldProperty)) {
                continue;
            }

            $object->$newProperty = $object->$oldProperty;
            unset($object->$oldProperty);
        }

        return $object;
    }
}