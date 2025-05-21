<?php

namespace Api\Idosell\Exceptions;

use Exception;

class IdosellApiException extends Exception
{
    protected $errorData;

    public function __construct($message = "", $code = 0, $errorData = null)
    {
        $this->errorData = $errorData;
        parent::__construct($message, $code);
    }

    public function getErrorData()
    {
        return $this->errorData;
    }
} 