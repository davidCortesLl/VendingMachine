<?php

declare(strict_types=1);

namespace Domain\Model;

class Item {
    public const VALID_NAMES = ['Water', 'Juice', 'Soda'];

    public function __construct(
        public string $selector,
        public string $name,
        public float $price,
        public int $count = 0
    ) {}

    public static function validateItemData(string $name, float $price, int $count): ?string {
        if ($price < 0) {
            return "Item price for '{$name}' cannot be negative";
        }
        if ($count < 0) {
            return "Item count for '{$name}' cannot be negative";
        }
        if (!in_array($name, self::VALID_NAMES, true)) {
            return "Item type '{$name}' is not allowed";
        }
        return null;
    }
}
