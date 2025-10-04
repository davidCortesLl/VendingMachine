<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Domain\Model\VendingMachine;
use Domain\Model\Coin;
use Domain\Model\Item;

class VendingMachineTest extends TestCase
{
    private function makeCoin(float $value, int $count = 1): Coin
    {
        return new Coin($value, $count);
    }

    private function makeItem(string $selector = 'A1', string $name = 'Water', float $price = 1.0, int $count = 1): Item
    {
        return new Item($selector, $name, $price, $count);
    }

    public function testConstructorSetsProperties(): void
    {
        $items = [$this->makeItem('A1', 'Water', 1.0, 2)];
        $coins = [$this->makeCoin(0.25, 5)];
        $inserted = [$this->makeCoin(1.0, 1)];
        $vm = new VendingMachine($items, $coins, $inserted);
        $this->assertSame($items, $vm->items);
        $this->assertSame($coins, $vm->coins);
        $this->assertSame($inserted, $vm->insertedMoney);
    }

    public function testInsertCoinAddsNewCoin(): void
    {
        $vm = new VendingMachine([], [], []);
        $vm->insertCoin(0.10);
        $this->assertCount(1, $vm->insertedMoney);
        $this->assertSame(0.10, $vm->insertedMoney[0]->value);
        $this->assertSame(1, $vm->insertedMoney[0]->count);
    }

    public function testInsertCoinIncrementsExisting(): void
    {
        $vm = new VendingMachine([], [], [new Coin(0.25, 2)]);
        $vm->insertCoin(0.25);
        $this->assertCount(1, $vm->insertedMoney);
        $this->assertSame(3, $vm->insertedMoney[0]->count);
    }

    public function testAddCoinAddsNewCoin(): void
    {
        $vm = new VendingMachine([], [], []);
        $vm->addCoin(1.00);
        $this->assertCount(1, $vm->coins);
        $this->assertSame(1.00, $vm->coins[0]->value);
        $this->assertSame(1, $vm->coins[0]->count);
    }

    public function testAddCoinIncrementsExisting(): void
    {
        $vm = new VendingMachine([], [new Coin(0.05, 2)], []);
        $vm->addCoin(0.05);
        $this->assertCount(1, $vm->coins);
        $this->assertSame(3, $vm->coins[0]->count);
    }

    public function testIsInsertedMoneyEmptyTrue(): void
    {
        $vm = new VendingMachine([], [], []);
        $this->assertTrue($vm->isInsertedMoneyEmpty());
    }

    public function testIsInsertedMoneyEmptyFalse(): void
    {
        $vm = new VendingMachine([], [], [new Coin(0.10, 1)]);
        $this->assertFalse($vm->isInsertedMoneyEmpty());
    }

    public function testReturnInsertedCoinsEmptiesInsertedMoney(): void
    {
        $coins = [new Coin(0.25, 2), new Coin(1.00, 1)];
        $vm = new VendingMachine([], [], $coins);
        $returned = $vm->returnInsertedCoins();
        $this->assertEquals($coins, $returned);
        $this->assertEmpty($vm->insertedMoney);
    }
}

