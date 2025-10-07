<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Domain\Model\VendingMachine;
use Domain\Model\Item;

class VendingMachineTest extends TestCase
{
    private function makeItem(string $selector = 'A1', string $name = 'Water', float $price = 1.0, int $count = 1): Item
    {
        return new Item($selector, $name, $price, $count);
    }

    public function testConstructorSetsProperties(): void
    {
        $items = [$this->makeItem('A1', 'Water', 1.0, 2)];
        $coins = ['0.25' => 5];
        $inserted = 1.0;

        $vm = new VendingMachine($items, $coins, $inserted);

        $this->assertSame($items, $vm->items);
        $this->assertSame($coins, $vm->coins);
        $this->assertSame($inserted, $vm->insertedMoney);
    }

    public function testInsertCoinSumsInsertedMoneyAndAddsCoin(): void
    {
        $vm = new VendingMachine([], [], 0.0);

        $vm->insertCoin(0.10);

        $this->assertSame(0.10, $vm->insertedMoney);
        $this->assertArrayHasKey('0.10', $vm->coins);
        $this->assertSame(1, $vm->coins['0.10']);
    }

    public function testInsertCoinIncrementsExistingCoin(): void
    {
        $vm = new VendingMachine([], ['0.25' => 2], 0.0);

        $vm->insertCoin(0.25);

        $this->assertSame(0.25, $vm->insertedMoney);
        $this->assertSame(3, $vm->coins['0.25']);
    }

    public function testAddCoinAddsNewCoin(): void
    {
        $vm = new VendingMachine([], [], 0.0);

        $vm->addCoin(1.00);

        $this->assertArrayHasKey('1.00', $vm->coins);
        $this->assertSame(1, $vm->coins['1.00']);
    }

    public function testAddCoinIncrementsExisting(): void
    {
        $vm = new VendingMachine([], ['0.05' => 2], 0.0);

        $vm->addCoin(0.05);

        $this->assertSame(3, $vm->coins['0.05']);
    }

    public function testIsInsertedMoneyEmptyTrue(): void
    {
        $vm = new VendingMachine([], [], 0.0);

        $this->assertTrue($vm->isInsertedMoneyEmpty());
    }

    public function testIsInsertedMoneyEmptyFalse(): void
    {
        $vm = new VendingMachine([], [], 0.10);

        $this->assertFalse($vm->isInsertedMoneyEmpty());
    }

    public function testReturnInsertedCoinsGreedy(): void
    {
        $coins = ['0.25' => 2, '0.10' => 1];
        $vm = new VendingMachine([], $coins, 0.60);

        $returned = $vm->returnInsertedCoins();

        $this->assertCount(2, $returned);
        $this->assertEquals(['value' => 0.25, 'count' => 2], $returned[0]);
        $this->assertEquals(['value' => 0.10, 'count' => 1], $returned[1]);
        $this->assertSame(0.0, $vm->insertedMoney);
    }

    public function testReturnInsertedCoinsNotEnoughChange(): void
    {
        $coins = ['0.25' => 1, '0.10' => 1];
        $vm = new VendingMachine([], $coins, 0.60);

        $returned = $vm->returnInsertedCoins();

        $this->assertSame([], $returned);
        $this->assertSame(0.0, $vm->insertedMoney);
    }

    public function testSelectItemSuccess(): void
    {
        $item = $this->makeItem('A1', 'Water', 0.5, 2);
        $coins = ['0.5' => 2, '0.25' => 2];
        $vm = new VendingMachine([$item], $coins, 0.5);

        $result = $vm->selectItem('A1');

        $this->assertEquals('Water', $result['item']['name']);
        $this->assertEquals([], $result['change']);
        $this->assertSame(1, $result['status']['items'][0]->count);
        $this->assertSame(0.0, $result['status']['insertedMoney']);
    }

    public function testSelectItemWithChange(): void
    {
        $item = $this->makeItem('A1', 'Water', 0.5, 2);
        $coins = ['0.5' => 2, '0.25' => 2];
        $vm = new VendingMachine([$item], $coins, 1.0);

        $result = $vm->selectItem('A1');

        $this->assertEquals('Water', $result['item']['name']);
        $this->assertNotEmpty($result['change']);
        $this->assertSame(1, $result['status']['items'][0]->count);
        $this->assertSame(0.0, $result['status']['insertedMoney']);
    }

    public function testSelectItemInsufficientFunds(): void
    {
        $item = $this->makeItem('A1', 'Water', 1.0, 2);
        $coins = ['0.5' => 2];
        $vm = new VendingMachine([$item], $coins, 0.5);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient funds');

        $vm->selectItem('A1');
    }

    public function testSelectItemOutOfStock(): void
    {
        $item = $this->makeItem('A1', 'Water', 0.5, 0);
        $coins = ['0.5' => 2];
        $vm = new VendingMachine([$item], $coins, 0.5);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Item out of stock');

        $vm->selectItem('A1');
    }

    public function testSelectItemNotFound(): void
    {
        $item = $this->makeItem('A1', 'Water', 0.5, 2);
        $coins = ['0.5' => 2];
        $vm = new VendingMachine([$item], $coins, 0.5);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Item not found');

        $vm->selectItem('B1');
    }

    public function testSelectItemCannotReturnExactChange(): void
    {
        $item = $this->makeItem('A1', 'Water', 0.5, 2);
        $coins = ['0.25' => 1];
        $vm = new VendingMachine([$item], $coins, 1.0);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot return exact change');

        $vm->selectItem('A1');
    }

    public function testGetChangeFromCoinsExact(): void
    {
        $coins = ['0.5' => 2, '0.25' => 2];
        $vm = new VendingMachine([], $coins, 0.0);
        $ref = new \ReflectionClass($vm);
        $method = $ref->getMethod('getChangeFromCoins');
        $method->setAccessible(true);

        $result = $method->invoke($vm, 1.0);

        $this->assertCount(1, $result); // Solo un tipo de moneda
        $this->assertEquals(['value' => 0.5, 'count' => 2], $result[0]);
    }

    public function testGetChangeFromCoinsNotEnough(): void
    {
        $coins = ['0.25' => 1];
        $vm = new VendingMachine([], $coins, 0.0);
        $ref = new \ReflectionClass($vm);
        $method = $ref->getMethod('getChangeFromCoins');
        $method->setAccessible(true);

        $result = $method->invoke($vm, 0.5);

        $this->assertSame([], $result);
    }

    public function testGetChangeFromCoinsGreedy(): void
    {
        $coins = ['1.00' => 1, '0.5' => 2, '0.2' => 3, '0.1' => 5];
        $vm = new VendingMachine([], $coins, 0.0);
        $ref = new \ReflectionClass($vm);
        $method = $ref->getMethod('getChangeFromCoins');
        $method->setAccessible(true);

        $result = $method->invoke($vm, 1.8);
        $this->assertNotEmpty($result);
        $total = 0.0;
        foreach ($result as $coin) {
            $total += $coin['value'] * $coin['count'];
        }
        $this->assertEquals(1.8, $total);
    }

    public function testIsValidCoinValue(): void
    {
        $this->assertTrue(VendingMachine::isValidCoinValue(0.05));
        $this->assertTrue(VendingMachine::isValidCoinValue(0.10));
        $this->assertTrue(VendingMachine::isValidCoinValue(0.25));
        $this->assertTrue(VendingMachine::isValidCoinValue(1.00));
        $this->assertFalse(VendingMachine::isValidCoinValue(0.50));
        $this->assertFalse(VendingMachine::isValidCoinValue(-0.05));
    }

    public function testValidateCoinDataValid(): void
    {
        $this->assertNull(VendingMachine::validateCoinData(0.10, 2));
    }

    public function testValidateCoinDataNegativeValue(): void
    {
        $this->assertSame('Coin value cannot be negative', VendingMachine::validateCoinData(-0.10, 2));
    }

    public function testValidateCoinDataNegativeCount(): void
    {
        $this->assertSame('Coin count cannot be negative', VendingMachine::validateCoinData(0.10, -1));
    }

    public function testValidateCoinDataInvalidValue(): void
    {
        $this->assertSame("Coin value '0.5' is not allowed", VendingMachine::validateCoinData(0.50, 1));
    }

    public function testServiceSetMachineSuccess(): void
    {
        $vm = new VendingMachine([], [], 1.0);

        $itemsData = [
            ['selector' => 'A1', 'name' => 'Water', 'price' => 0.65, 'count' => 5],
            ['selector' => 'B1', 'name' => 'Juice', 'price' => 1.00, 'count' => 3]
        ];

        $coinsData = [
            ['value' => 0.25, 'count' => 10],
            ['value' => 1.00, 'count' => 5]
        ];

        $vm->serviceSetMachine($itemsData, $coinsData);

        $this->assertCount(2, $vm->items);
        $this->assertEquals('Water', $vm->items[0]->name);
        $this->assertEquals('Juice', $vm->items[1]->name);
        $this->assertEquals(['0.25' => 10, '1.00' => 5], $vm->coins);
        $this->assertEquals(0.0, $vm->insertedMoney);
    }

    public function testServiceSetMachineEmptyArrays(): void
    {
        $vm = new VendingMachine([
            $this->makeItem('A1', 'Water', 1.0, 1)
        ], ['0.25' => 5], 2.0);

        $vm->serviceSetMachine([], []);

        $this->assertCount(0, $vm->items);
        $this->assertCount(0, $vm->coins);
        $this->assertEquals(0.0, $vm->insertedMoney);
    }

    public function testServiceSetMachineInvalidItemName(): void
    {
        $vm = new VendingMachine([], []);

        $itemsData = [
            ['selector' => 'A1', 'name' => '', 'price' => 1.0, 'count' => 5]
        ];

        $coinsData = [
            ['value' => 0.25, 'count' => 10]
        ];

        $this->expectException(\Exception::class);
        $vm->serviceSetMachine($itemsData, $coinsData);
    }

    public function testServiceSetMachineInvalidItemPrice(): void
    {
        $vm = new VendingMachine([], []);

        $itemsData = [
            ['selector' => 'A1', 'name' => 'Water', 'price' => -1.0, 'count' => 5]
        ];

        $coinsData = [
            ['value' => 0.25, 'count' => 10]
        ];

        $this->expectException(\Exception::class);
        $vm->serviceSetMachine($itemsData, $coinsData);
    }

    public function testServiceSetMachineInvalidItemCount(): void
    {
        $vm = new VendingMachine([], []);

        $itemsData = [
            ['selector' => 'A1', 'name' => 'Water', 'price' => 1.0, 'count' => -1]
        ];

        $coinsData = [
            ['value' => 0.25, 'count' => 10]
        ];

        $this->expectException(\Exception::class);
        $vm->serviceSetMachine($itemsData, $coinsData);
    }

    public function testServiceSetMachineInvalidCoinValue(): void
    {
        $vm = new VendingMachine([], []);

        $itemsData = [
            ['selector' => 'A1', 'name' => 'Water', 'price' => 1.0, 'count' => 5]
        ];

        $coinsData = [
            ['value' => 0.50, 'count' => 10]
        ];

        $this->expectException(\Exception::class);
        $vm->serviceSetMachine($itemsData, $coinsData);
    }

    public function testServiceSetMachineNegativeCoinValue(): void
    {
        $vm = new VendingMachine([], []);

        $itemsData = [
            ['selector' => 'A1', 'name' => 'Water', 'price' => 1.0, 'count' => 5]
        ];

        $coinsData = [
            ['value' => -0.25, 'count' => 10]
        ];

        $this->expectException(\Exception::class);
        $vm->serviceSetMachine($itemsData, $coinsData);
    }

    public function testServiceSetMachineNegativeCoinCount(): void
    {
        $vm = new VendingMachine([], []);

        $itemsData = [
            ['selector' => 'A1', 'name' => 'Water', 'price' => 1.0, 'count' => 5]
        ];

        $coinsData = [
            ['value' => 0.25, 'count' => -10]
        ];

        $this->expectException(\Exception::class);
        $vm->serviceSetMachine($itemsData, $coinsData);
    }

    public function testServiceSetMachineCoinKeyNormalization(): void
    {
        $vm = new VendingMachine([], []);

        $itemsData = [
            ['selector' => 'A1', 'name' => 'Water', 'price' => 1.0, 'count' => 5]
        ];

        $coinsData = [
            ['value' => 0.1, 'count' => 10],
            ['value' => 1, 'count' => 5]
        ];

        $vm->serviceSetMachine($itemsData, $coinsData);

        $this->assertArrayHasKey('0.10', $vm->coins);
        $this->assertArrayHasKey('1.00', $vm->coins);
        $this->assertEquals(10, $vm->coins['0.10']);
        $this->assertEquals(5, $vm->coins['1.00']);
    }

    public function testJsonSerialize(): void
    {
        $items = [
            $this->makeItem('A1', 'Water', 1.0, 2),
            $this->makeItem('B2', 'Soda', 1.5, 1)
        ];
        $coins = ['0.25' => 3, '1.00' => 1];
        $inserted = 2.0;
        $vm = new VendingMachine($items, $coins, $inserted);

        $expected = [
            'items' => $items,
            'coins' => $vm->coins,
            'insertedMoney' => $inserted,
        ];

        $this->assertSame(
            json_encode($expected),
            json_encode($vm)
        );
    }
}
