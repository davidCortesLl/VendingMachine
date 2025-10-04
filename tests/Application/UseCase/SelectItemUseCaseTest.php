<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Application\UseCase\SelectItemUseCase;
use Domain\Repository\VendingMachineRepository;
use Domain\Model\VendingMachine;
use Domain\Model\Item;
use Domain\Model\Coin;

class SelectItemUseCaseTest extends TestCase
{
    private function getMachineMock($items, $insertedMoney, $coins) {
        $machine = $this->createMock(VendingMachine::class);
        $machine->items = $items;
        $machine->insertedMoney = $insertedMoney;
        $machine->coins = $coins;
        // Simula isInsertedMoneyEmpty() según el estado real de insertedMoney
        $machine->method('isInsertedMoneyEmpty')->willReturnCallback(function() use ($machine) {
            if (!is_array($machine->insertedMoney)) return true;
            foreach ($machine->insertedMoney as $coin) {
                if ($coin instanceof Coin && $coin->count > 0) return false;
                if (is_object($coin) && property_exists($coin, 'count') && $coin->count > 0) return false;
            }
            return true;
        });
        return $machine;
    }

    public function testSelectItemSuccessExactFunds(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $item = (object)['selector' => '1', 'name' => 'Water', 'price' => 1.0, 'count' => 2];
        $inserted = [(object)['value' => 1.0, 'count' => 1]];
        $coins = [(object)['value' => 0.5, 'count' => 2]];
        $machine = $this->getMachineMock([$item], $inserted, $coins);

        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);

        $useCase = new SelectItemUseCase($repo);
        $result = $useCase->execute('1');

        $this->assertEquals('Water', $result['item']['name']);
        $this->assertEquals([], $result['change']);
    }

    public function testSelectItemSuccessWithChangeFromInserted(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $item = (object)['selector' => '1', 'name' => 'Water', 'price' => 0.75, 'count' => 2];
        $inserted = [(object)['value' => 1.0, 'count' => 1], (object)['value' => 0.25, 'count' => 1]];
        $coins = [(object)['value' => 0.25, 'count' => 2]];
        $machine = $this->getMachineMock([$item], $inserted, $coins);

        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);

        $useCase = new SelectItemUseCase($repo);
        $result = $useCase->execute('1');

        $this->assertEquals('Water', $result['item']['name']);
        $this->assertArrayHasKey('0.25', $result['change']);
    }

    public function testSelectItemSuccessChangeFromMachineOnly(): void
    {
        $item = (object)['selector' => '1', 'name' => 'Water', 'price' => 0.5, 'count' => 2];
        $inserted = [(object)['value' => 1.0, 'count' => 1]];
        $coins = [
            (object)['value' => 0.5, 'count' => 2],
        ];
        $machine = $this->getMachineMock([$item], $inserted, $coins);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);

        $useCase = new SelectItemUseCase($repo);
        $result = $useCase->execute('1');

        $this->assertEquals('Water', $result['item']['name']);
        $this->assertEquals(['0.5' => 1], $result['change']);
        $this->assertEquals(1, $machine->items[0]->count);
        $this->assertEquals(1, $machine->coins[0]->count);
        $this->assertEmpty($machine->insertedMoney);
    }

    public function testSelectItemSuccessChangeMixedInsertedAndMachine(): void
    {
        $item = new Item('1', 'Snack', 2, 2);
        $inserted = [
            new Coin(5, 1),
            new Coin(1, 1),
        ];
        $coins = [
            new Coin(2, 1),
            new Coin(1, 2),
        ];
        $machine = new VendingMachine([$item], $coins, $inserted);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);

        $useCase = new SelectItemUseCase($repo);
        $result = $useCase->execute('1');

        $this->assertEquals('Snack', $result['item']['name']);
        $this->assertEquals(['2' => 1, '1' => 2], $result['change']);
        $this->assertEquals(1, $machine->items[0]->count);
        $this->assertEquals(1, $machine->coins[1]->count);
        $this->assertEquals(0, $machine->coins[0]->count);
        $this->assertEmpty($machine->insertedMoney);
    }

    public function testSelectItemNotFound(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->getMachineMock([], [], []);
        $repo->method('get')->willReturn($machine);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Item not found');

        $useCase = new SelectItemUseCase($repo);
        $useCase->execute('99');
    }

    public function testSelectItemOutOfStock(): void
    {
        $item = (object)['selector' => '1', 'name' => 'Water', 'price' => 1.0, 'count' => 0];
        $machine = $this->getMachineMock([$item], [], []);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Item out of stock');

        $useCase = new SelectItemUseCase($repo);
        $useCase->execute('1');
    }

    public function testSelectItemInsufficientFunds(): void
    {
        $item = (object)['selector' => '1', 'name' => 'Water', 'price' => 1.0, 'count' => 2];
        $inserted = [(object)['value' => 0.5, 'count' => 1]];
        $machine = $this->getMachineMock([$item], $inserted, []);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient funds');

        $useCase = new SelectItemUseCase($repo);
        $useCase->execute('1');
    }

    public function testSelectItemCannotProvideChange(): void
    {
        $item = (object)['selector' => '1', 'name' => 'Water', 'price' => 0.5, 'count' => 2];
        $inserted = [(object)['value' => 1.0, 'count' => 1]];
        $coins = [(object)['value' => 0.25, 'count' => 0]];
        $machine = $this->getMachineMock([$item], $inserted, $coins);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot provide exact change');

        $useCase = new SelectItemUseCase($repo);
        $useCase->execute('1');
    }

    public function testRepositoryGetThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willThrowException(new Exception('Repo error'));

        $this->expectException(Exception::class);

        $useCase = new SelectItemUseCase($repo);
        $useCase->execute('1');
    }

    public function testRepositorySaveThrows(): void
    {
        $item = (object)['selector' => '1', 'name' => 'Water', 'price' => 1.0, 'count' => 2];
        $inserted = [(object)['value' => 1.0, 'count' => 1]];
        $machine = $this->getMachineMock([$item], $inserted, []);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);
        $repo->method('save')->willThrowException(new Exception('Save error'));

        $this->expectException(Exception::class);

        $useCase = new SelectItemUseCase($repo);
        $useCase->execute('1');
    }

    public function testCalculateChangeBranches(): void
    {
        $useCase = new SelectItemUseCase($this->createMock(VendingMachineRepository::class));
        $reflection = new \ReflectionClass($useCase);
        $method = $reflection->getMethod('calculateChange');

        // Case change < 0.01
        $coins = [new Coin(1, 10)];
        $this->assertEquals([], $method->invoke($useCase, 0.005, $coins));

        // Case can't give exact change
        $coins = [new Coin(2, 1)];
        $this->assertNull($method->invoke($useCase, 3, $coins));

        // Case can give exact change
        $coins = [new Coin(2, 1), new \Domain\Model\Coin(1, 1)];
        $this->assertEquals(['2' => 1, '1' => 1], $method->invoke($useCase, 3, $coins));
    }

    public function testGetChangeFromInsertedMoneyBranches(): void
    {
        $useCase = new SelectItemUseCase($this->createMock(VendingMachineRepository::class));
        $reflection = new \ReflectionClass($useCase);
        $method = $reflection->getMethod('getChangeFromInsertedMoney');
        $method->setAccessible(true);

        // Case change 0, inserted coins empty
        $machine = new VendingMachine([], [], []);
        $this->assertEquals([[], 0.0], $method->invoke($useCase, 0.0, $machine));

        // Case change > 0, but inserted coins empty
        $this->assertEquals([[], 1.0], $method->invoke($useCase, 1.0, $machine));
    }

    public function testGetChangeFromMachineCoinsBranches(): void
    {
        $useCase = new SelectItemUseCase($this->createMock(VendingMachineRepository::class));
        $reflection = new \ReflectionClass($useCase);
        $method = $reflection->getMethod('getChangeFromMachineCoins');
        $method->setAccessible(true);

        // Case change 0, no modification needed
        $machine = new \stdClass();
        $machine->coins = [];
        $changeCoins = [];
        $method->invoke($useCase, 0.0, $machine, $changeCoins);
        $this->assertEquals([], $changeCoins);

        // Case exception if can't give exact change
        $machine->coins = [new Coin(2, 1)];
        $changeCoins = [];
        $this->expectException(\Exception::class);
        $method->invoke($useCase, 3.0, $machine, $changeCoins);
    }

    public function testExecuteNoChangeNeeded(): void
    {
        $item = new Item('1', 'Water', 1.0, 2);
        $inserted = [new Coin(1.0, 1)];
        $coins = [new Coin(0.5, 2)];
        $machine = new VendingMachine([$item], $coins, $inserted);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);

        $useCase = new SelectItemUseCase($repo);
        $result = $useCase->execute('1');

        $this->assertEquals('Water', $result['item']['name']);
        $this->assertEquals([], $result['change']);
        $this->assertEquals(1, $machine->items[0]->count);
        $this->assertEmpty($machine->insertedMoney);
    }

    public function testExecuteCoversInsertedAndMachineChangeBranch(): void
    {
        $item = new Item('1', 'Snack', 3, 2);
        $inserted = [
            new Coin(2, 1), // Inserta 2
            new Coin(1, 1), // Inserta 1
        ];
        $coins = [
            new Coin(2, 1), // Máquina tiene 1x2
            new Coin(1, 1), // Máquina tiene 1x1
        ];
        $machine = new VendingMachine([$item], $coins, $inserted);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);

        $addCoinCalls = [];
        $machine->addCoin = function($value) use (&$addCoinCalls) { $addCoinCalls[] = $value; };

        $useCase = new SelectItemUseCase($repo);
        $result = $useCase->execute('1');

        $this->assertEquals('Snack', $result['item']['name']);
        $this->assertEquals([], $result['change']);
        $this->assertEquals(1, $machine->items[0]->count);
        $this->assertEmpty($machine->insertedMoney);
    }

    public function testGetChangeFromInsertedMoneyUsesInsertedCoins(): void
    {
        $useCase = new SelectItemUseCase($this->createMock(VendingMachineRepository::class));
        $reflection = new \ReflectionClass($useCase);
        $method = $reflection->getMethod('getChangeFromInsertedMoney');
        $method->setAccessible(true);

        // Prepare machine with inserted coins sufficient for change
        $inserted = [new Coin(1.0, 2), new Coin(0.5, 1)];
        $machine = new VendingMachine([], [], $inserted);

        // Request 1.5 as change
        [$changeCoins, $remainingChange] = $method->invoke($useCase, 1.5, $machine);
        $this->assertEquals(['1' => 1, '0.5' => 1], $changeCoins);
        $this->assertEquals(0.0, $remainingChange);

        // Verify that the counts of the inserted coins were reduced correctly
        $this->assertEquals(1, $machine->insertedMoney[0]->count); // 1 1.0 coin left
        $this->assertEquals(0, $machine->insertedMoney[1]->count); // 0 0.5 coins left
    }
}
