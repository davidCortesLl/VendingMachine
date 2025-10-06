<?php

declare(strict_types=1);

namespace Domain\Model;

class VendingMachine {
    /** @var Item[] */
    public array $items;
    /** @var Coin[] */
    public array $coins;
    public float $insertedMoney;

    public function __construct(
        array $items,
        array $coins,
        float $insertedMoney = 0.0
    ) {
        $this->items = $items;
        $this->coins = $coins;
        $this->insertedMoney = $insertedMoney;
    }

    public function insertCoin(float $value): void {
        $this->insertedMoney += $value;
        $this->addCoin($value);
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
        return $this->insertedMoney <= 0.00001;
    }

    /**
     * @return Coin[]
     */
    public function returnInsertedCoins(): array {
        $amount = $this->insertedMoney;
        $result = $this->getChangeFromCoins($amount);
        $this->insertedMoney = 0.0;

        return $result;
    }

    /**
     * @return Coin[]
     */
    private function getChangeFromCoins(float $amount): array {
        $change = [];
        usort($this->coins, fn($a, $b) => $b->value <=> $a->value);

        foreach ($this->coins as $coin) {
            if ($amount < 0.00001) break;
            $needed = (int) floor(round($amount + 0.00001, 2) / $coin->value);
            $take = min($needed, $coin->count);
            if ($take > 0) {
                $change[] = new Coin($coin->value, $take);
                $coin->count -= $take;
                $amount -= $coin->value * $take;
                $amount = round($amount, 2);
            }
        }

        if ($amount > 0.00001) {
            // Can't retun change, restore coins
            foreach ($change as $c) {
                foreach ($this->coins as $coin) {
                    if ($coin->value === $c->value) {
                        $coin->count += $c->count;
                    }
                }
            }

            return [];
        }

        return $change;
    }

    /**
     * @throws \Exception
     */
    public function selectItem(string $selector): array {
        $item = null;
        foreach ($this->items as $i) {
            if ($i->selector === $selector) {
                $item = $i;
                break;
            }
        }
        if (!$item) {
            throw new \Exception("Item not found");
        }
        if ($item->count < 1) {
            throw new \Exception("Item out of stock");
        }

        $inserted = $this->insertedMoney;
        if ($inserted < $item->price) {
            throw new \Exception("Insufficient funds. Inserted: $inserted, Price: $item->price");
        }

        $change = round($inserted - $item->price, 2);
        $changeCoins = [];
        if ($change > 0.00001) {
            $changeCoins = $this->getChangeFromCoins($change);
            if (empty($changeCoins)) {
                throw new \Exception("Cannot return exact change");
            }
        }

        $item->count--;
        $this->insertedMoney = 0.0;

        return [
            'item' => [
                'selector' => $item->selector,
                'name' => $item->name,
                'price' => $item->price
            ],
            'change' => array_map(fn($coin) => ['value' => $coin->value, 'count' => $coin->count], $changeCoins),
            'status' => [
                'items' => $this->items,
                'coins' => $this->coins,
                'insertedMoney' => $this->insertedMoney
            ]
        ];
    }
}
