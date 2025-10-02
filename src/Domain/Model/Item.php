<?php

declare(strict_types=1);

namespace Domain\Model;

class Item {

    public function __construct(
        public string $selector,
        public string $name,
        public float $price,
        public int $count = 0
    ) {}
}

