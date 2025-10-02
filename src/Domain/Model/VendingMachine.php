<?php

declare(strict_types=1);

namespace Domain\Model;

class VendingMachine {

    public function __construct(
        /** @var Item[] $items */
        public array $items,
        /** @var Coin[] $coins */
        public array $coins,
        public float $insertedMoney = 0.0
    ) {}

    public function insertCoin(float $value): void {
        foreach ($this->coins as $coin) {
            if ($coin->value === $value) {
                $coin->count += 1;
                $this->insertedMoney += $value;

                return;
            }
        }

        throw new \Exception("Coin of value $value not accepted.");
    }
}

