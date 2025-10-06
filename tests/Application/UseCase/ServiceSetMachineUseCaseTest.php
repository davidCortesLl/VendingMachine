<?php

declare(strict_types=1);

namespace Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Exception;
use Application\UseCase\ServiceSetMachineUseCase;
use Domain\Repository\VendingMachineRepository;
use Domain\Model\VendingMachine;

class ServiceSetMachineUseCaseTest extends TestCase
{
    public function testSetMachineSuccess(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->createMock(VendingMachine::class);
        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);
        $machine->method('jsonSerialize')->willReturn([
            'coins' => [1.0],
            'insertedMoney' => 0.0
        ]);

        $useCase = new ServiceSetMachineUseCase($repo);
        $result = $useCase->execute([], []);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('coins', $result);
        $this->assertArrayHasKey('insertedMoney', $result);
        $this->assertEquals([
            'coins' => [1.0],
            'insertedMoney' => 0.0
        ], $result);
    }

    public function testSetMachineInvalidItemThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);

        $items = [
            ['selector' => '1', 'name' => '', 'price' => 1.0, 'count' => 5],
        ];
        $coins = [
            ['value' => 0.25, 'count' => 10],
        ];
        $useCase = new ServiceSetMachineUseCase($repo);

        $this->expectException(Exception::class);
        $useCase->execute($items, $coins);
    }

    public function testSetMachineInvalidCoinThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);

        $items = [
            ['selector' => '1', 'name' => 'Water', 'price' => 1.0, 'count' => 5],
        ];
        $coins = [
            ['value' => 0.03, 'count' => 10],
        ];
        $useCase = new ServiceSetMachineUseCase($repo);

        $this->expectException(Exception::class);
        $useCase->execute($items, $coins);
    }

    public function testRepositorySaveThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('save')->willThrowException(new Exception('Save error'));

        $items = [
            ['selector' => '1', 'name' => 'Water', 'price' => 1.0, 'count' => 5],
        ];
        $coins = [
            ['value' => 0.25, 'count' => 10],
        ];
        $useCase = new ServiceSetMachineUseCase($repo);

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
