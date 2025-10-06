<?php

declare(strict_types=1);

namespace Domain\Model;

class VendingMachine implements \JsonSerializable {
    public const VALID_COIN_VALUES = [0.05, 0.10, 0.25, 1.00];

    /** @var Item[] */
    public array $items;
    /** @var array<string, int> */
    public array $coins;
    public float $insertedMoney;

    public function __construct(
        array $items,
        array $coins,
        float $insertedMoney = 0.0
    ) {
        $this->items = $items;
        // Normalise coin keys to 2 decimals
        $normalizedCoins = [];
        foreach ($coins as $k => $v) {
            $key = number_format((float)$k, 2, '.', '');
            $normalizedCoins[$key] = $v;
        }
        $this->coins = $normalizedCoins;
        $this->insertedMoney = $insertedMoney;
    }

    public function insertCoin(float $value): void {
        $this->insertedMoney += $value;
        $this->addCoin($value);
    }

    public function addCoin(float $value): void {
        $key = number_format($value, 2, '.', '');
        if (isset($this->coins[$key])) {
            $this->coins[$key]++;
        } else {
            $this->coins[$key] = 1;
        }
    }

    public function isInsertedMoneyEmpty(): bool {
        return $this->insertedMoney <= 0.00001;
    }

    /**
     * @return array<int, array{value: float, count: int}>
     */
    public function returnInsertedCoins(): array {
        $amount = $this->insertedMoney;
        $result = $this->getChangeFromCoins($amount);
        $this->insertedMoney = 0.0;
        return $result;
    }

    /**
     * @return array<int, array{value: float, count: int}>
     */
    private function getChangeFromCoins(float $amount): array {
        $change = [];
        // Ordenar de mayor a menor valor
        $coinValues = array_keys($this->coins);
        usort($coinValues, fn($a, $b) => (float)$b <=> (float)$a);
        $coinsBackup = $this->coins;
        foreach ($coinValues as $value) {
            $coinKey = number_format((float)$value, 2, '.', '');
            $coinValue = (float)$coinKey;
            if ($amount < 0.00001) break;
            $available = $this->coins[$coinKey] ?? 0;
            $needed = (int) floor(round($amount + 0.00001, 2) / $coinValue);
            $take = min($needed, $available);
            if ($take > 0) {
                $change[] = ['value' => $coinValue, 'count' => $take];
                $this->coins[$coinKey] -= $take;
                $amount -= $coinValue * $take;
                $amount = round($amount, 2);
            }
        }
        if ($amount > 0.00001) {
            $this->coins = $coinsBackup;
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
            'change' => $changeCoins,
            'status' => [
                'items' => $this->items,
                'coins' => $this->coins,
                'insertedMoney' => $this->insertedMoney
            ]
        ];
    }

    public static function isValidCoinValue(float|string $value): bool {
        $floatValue = (float)$value;
        return in_array($floatValue, self::VALID_COIN_VALUES, true);
    }

    public static function validateCoinData(float|string $value, int $count): ?string {
        $floatValue = (float)$value;
        if ($floatValue < 0) {
            return "Coin value cannot be negative";
        }
        if ($count < 0) {
            return "Coin count cannot be negative";
        }
        if (!self::isValidCoinValue($floatValue)) {
            return "Coin value '$floatValue' is not allowed";
        }
        return null;
    }

    public function jsonSerialize(): array {
        return [
            'items' => $this->items,
            'coins' => $this->coins,
            'insertedMoney' => $this->insertedMoney,
        ];
    }

}
