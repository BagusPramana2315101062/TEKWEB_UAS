<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    protected $items;

    public function __construct(array $items = [], $message = "Insufficient stock for one or more items.", $code = 422)
    {
        parent::__construct($message, $code);
        $this->items = $items;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
