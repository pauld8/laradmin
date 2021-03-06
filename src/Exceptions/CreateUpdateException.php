<?php

namespace Shemi\Laradmin\Exceptions;

use Exception;
use Throwable;

class CreateUpdateException extends Exception implements ExceptionContract
{

    public function __construct(string $message, Throwable $previous = null, int $code = 500)
    {
        $message = $message . ($previous ? ": {$previous->getMessage()}" : "");

        parent::__construct($message, $code, $previous);
    }

}