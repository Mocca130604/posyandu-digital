<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class LmsParameter
{
    public function __construct(
        public float $l,
        public float $m,
        public float $s,
    ) {}
}
