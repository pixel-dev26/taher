<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public string $skuCode;
    public string $godownName;
    public float $available;
    public float $requested;

    public function __construct(string $skuCode, string $godownName, float $available, float $requested)
    {
        $this->skuCode = $skuCode;
        $this->godownName = $godownName;
        $this->available = $available;
        $this->requested = $requested;

        parent::__construct(
            "Insufficient stock: SKU {$skuCode} in {$godownName} — Available: {$available}, Requested: {$requested}"
        );
    }
}
