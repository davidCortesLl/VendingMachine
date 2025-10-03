<?php

declare(strict_types=1);

namespace Domain\Model;

class VendingMachine {

    public function __construct(
        /** @var Item[] $items */
        public array $items,
        /** @var Coin[] $coins */
        public array $coins,
        /** @var Coin[] $insertedMoney */
        public array $insertedMoney = []
    ) {}

    public function insertCoin(float $value): void {
        foreach ($this->insertedMoney as $coin) {
            if ($coin->value === $value) {
                $coin->count += 1;

                return;
            }
        }

        $this->insertedMoney[] = new Coin($value, 1);
    }

    public function addCoin(float $value): void {
        foreach ($this->coins as $coin) {
            if ($coin->value === $value) {
                $coin->count++;

                return;
            }
        }

        $this->coins[] = new Coin($value, 1);
    }

    public function isInsertedMoneyEmpty(): bool {
        foreach ($this->insertedMoney as $coin) {
            if ($coin->count > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Coin[]
     */
    public function returnInsertedCoins(): array {
        $coins = $this->insertedMoney;
        $this->insertedMoney = [];

        return $coins;
    }
}
