<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Application\UseCase\ServiceSetMachineUseCase;
use Domain\Repository\VendingMachineRepository;
use Domain\Model\Item;
use Domain\Model\Coin;

class ServiceSetMachineUseCaseTest extends TestCase
{
    public function testSetMachineSuccess(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->expects($this->once())->method('save');
        $useCase = new ServiceSetMachineUseCase($repo);
        $items = [
            ['selector' => '1', 'name' => 'Water', 'price' => 1.0, 'count' => 5],
            ['selector' => '2', 'name' => 'Juice', 'price' => 1.5, 'count' => 3],
        ];
        $coins = [
            ['value' => 0.25, 'count' => 10],
            ['value' => 1.0, 'count' => 5],
        ];
        $useCase->execute($items, $coins);
        $this->assertTrue(true);
    }

    public function testSetMachineInvalidItemThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $useCase = new ServiceSetMachineUseCase($repo);
        $items = [
            ['selector' => '1', 'name' => '', 'price' => 1.0, 'count' => 5],
        ];
        $coins = [
            ['value' => 0.25, 'count' => 10],
        ];
        $this->expectException(Exception::class);
        $useCase->execute($items, $coins);
    }

    public function testSetMachineInvalidCoinThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $useCase = new ServiceSetMachineUseCase($repo);
        $items = [
            ['selector' => '1', 'name' => 'Water', 'price' => 1.0, 'count' => 5],
        ];
        $coins = [
            ['value' => 0.03, 'count' => 10],
        ];
        $this->expectException(Exception::class);
        $useCase->execute($items, $coins);
    }

    public function testRepositorySaveThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('save')->willThrowException(new Exception('Save error'));
        $useCase = new ServiceSetMachineUseCase($repo);
        $items = [
            ['selector' => '1', 'name' => 'Water', 'price' => 1.0, 'count' => 5],
        ];
        $coins = [
            ['value' => 0.25, 'count' => 10],
        ];
        $this->expectException(Exception::class);
        $useCase->execute($items, $coins);
    }

    public function testSetMachineEmptyArrays(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->expects($this->once())->method('save');
        $useCase = new ServiceSetMachineUseCase($repo);
        $useCase->execute([], []);
        $this->assertTrue(true);
    }
}

