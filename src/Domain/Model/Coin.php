<?php
declare(strict_types=1);

namespace Domain\Model;

class Coin {
    public const VALID_VALUES = [0.05, 0.10, 0.25, 1.00];

    public function __construct(
        public float $value,
        public int $count = 0
    ) {}

    public static function isValidValue(float $value): bool {
        return in_array($value, self::VALID_VALUES, true);
    }

    public static function validateCoinData(float $value, int $count): ?string {
        if ($value < 0) {
            return "Coin value cannot be negative";
        }
        if ($count < 0) {
            return "Coin count cannot be negative";
        }
        if (!self::isValidValue($value)) {
            return "Coin value '$value' is not allowed";
        }
        return null;
    }
}
