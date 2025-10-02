<?php
declare(strict_types=1);

namespace Domain\Model;

class Coin {

    public function __construct(
        public float $value,
        public int $count = 0
    ) {}
}
