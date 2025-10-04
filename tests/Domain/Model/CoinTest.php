<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Domain\Model\Coin;

class CoinTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $coin = new Coin(0.25, 3);
        $this->assertSame(0.25, $coin->value);
        $this->assertSame(3, $coin->count);
    }

    public function testIsValidValue(): void
    {
        $this->assertTrue(Coin::isValidValue(0.05));
        $this->assertTrue(Coin::isValidValue(0.10));
        $this->assertTrue(Coin::isValidValue(0.25));
        $this->assertTrue(Coin::isValidValue(1.00));
        $this->assertFalse(Coin::isValidValue(0.50));
        $this->assertFalse(Coin::isValidValue(-0.05));
    }

    public function testValidateCoinDataValid(): void
    {
        $this->assertNull(Coin::validateCoinData(0.10, 2));
    }

    public function testValidateCoinDataNegativeValue(): void
    {
        $this->assertSame('Coin value cannot be negative', Coin::validateCoinData(-0.10, 2));
    }

    public function testValidateCoinDataNegativeCount(): void
    {
        $this->assertSame('Coin count cannot be negative', Coin::validateCoinData(0.10, -1));
    }

    public function testValidateCoinDataInvalidValue(): void
    {
        $this->assertSame("Coin value '0.5' is not allowed", Coin::validateCoinData(0.50, 1));
    }
}

