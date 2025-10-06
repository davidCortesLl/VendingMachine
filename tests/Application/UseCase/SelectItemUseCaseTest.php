<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Application\UseCase\SelectItemUseCase;
use Domain\Repository\VendingMachineRepository;
use Domain\Model\VendingMachine;
use Domain\Model\Item;

class SelectItemUseCaseTest extends TestCase
{
    private function makeItem(string $selector, string $name, float $price, int $count): Item {
        return new Item($selector, $name, $price, $count);
    }

    public function testSelectItemSuccessExactFunds(): void
    {
        $item = $this->makeItem('1', 'Water', 1.0, 2);
        $coins = ['0.5' => 2];
        $machine = new VendingMachine([$item], $coins, 1.0);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);

        $useCase = new SelectItemUseCase($repo);
        $result = $useCase->execute('1');

        $this->assertEquals('Water', $result['item']['name']);
        $this->assertEquals([], $result['change']);
        $this->assertSame(1, $result['status']['items'][0]->count);
        $this->assertSame(0.0, $result['status']['insertedMoney']);
    }

    public function testSelectItemSuccessWithChangeFromCoins(): void
    {
        $item = $this->makeItem('1', 'Juice', 1.0, 2);
        $coins = ['0.5' => 2, '0.25' => 2];
        $machine = new VendingMachine([$item], $coins, 1.5);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);

        $useCase = new SelectItemUseCase($repo);
        $result = $useCase->execute('1');

        $this->assertEquals('Juice', $result['item']['name']);
        $this->assertNotEmpty($result['change']);
        $this->assertSame(1, $result['status']['items'][0]->count);
        $this->assertSame(0.0, $result['status']['insertedMoney']);
    }

    public function testSelectItemInsufficientFunds(): void
    {
        $item = $this->makeItem('1', 'Water', 1.0, 2);
        $coins = ['0.5' => 2];
        $machine = new VendingMachine([$item], $coins, 0.5);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);

        $useCase = new SelectItemUseCase($repo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient funds');
        $useCase->execute('1');
    }

    public function testSelectItemCannotReturnExactChange(): void
    {
        $item = $this->makeItem('1', 'Juice', 1.0, 2);
        $coins = ['0.25' => 1];
        $machine = new VendingMachine([$item], $coins, 1.5);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);

        $useCase = new SelectItemUseCase($repo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot return exact change');
        $useCase->execute('1');
    }
}
