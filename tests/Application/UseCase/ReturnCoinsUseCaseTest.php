<?php

declare(strict_types=1);

namespace tests\Application\UseCase;

use Exception;
use PHPUnit\Framework\TestCase;
use Application\UseCase\ReturnCoinsUseCase;
use Domain\Repository\VendingMachineRepository;
use Domain\Model\VendingMachine;

class ReturnCoinsUseCaseTest extends TestCase
{
    public function testReturnInsertedCoinsSuccess(): void
    {
        $machine = $this->createMock(VendingMachine::class);
        $coins = [
            ['value' => 0.25, 'count' => 2],
            ['value' => 1, 'count' => 1]
        ];
        $machine->expects($this->once())->method('returnInsertedCoins')->willReturn($coins);
        $machine->method('jsonSerialize')->willReturn([
            'coins' => [1.0],
            'insertedMoney' => 0.0
        ]);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);

        $useCase = new ReturnCoinsUseCase($repo);
        $result = $useCase->execute();

        $this->assertArrayHasKey('returnedCoins', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals($coins, $result['returnedCoins']);
        $this->assertEquals([
            'coins' => [1.0],
            'insertedMoney' => 0.0
        ], $result['status']);
    }

    public function testReturnNoInsertedCoins(): void
    {
        $machine = $this->createMock(VendingMachine::class);
        $machine->expects($this->once())->method('returnInsertedCoins')->willReturn([]);
        $machine->method('jsonSerialize')->willReturn([
            'coins' => [1.0],
            'insertedMoney' => 0.0
        ]);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);

        $useCase = new ReturnCoinsUseCase($repo);
        $result = $useCase->execute();

        $this->assertArrayHasKey('returnedCoins', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals([], $result['returnedCoins']);
        $this->assertEquals([
            'coins' => [1.0],
            'insertedMoney' => 0.0
        ], $result['status']);
    }

    public function testRepositoryGetThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willThrowException(new Exception('Repo error'));

        $useCase = new ReturnCoinsUseCase($repo);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error returning coins: Repo error');
        $useCase->execute();
    }

    public function testRepositorySaveThrows(): void
    {
        $machine = $this->createMock(VendingMachine::class);
        $machine->expects($this->once())->method('returnInsertedCoins')->willReturn([]);

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);
        $repo->method('save')->willThrowException(new Exception('Save error'));

        $useCase = new ReturnCoinsUseCase($repo);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error returning coins: Save error');
        $useCase->execute();
    }

    public function testReturnInsertedCoinsThrows(): void
    {
        $machine = $this->createMock(VendingMachine::class);
        $machine->method('returnInsertedCoins')->willThrowException(new Exception('Return error'));

        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willReturn($machine);

        $useCase = new ReturnCoinsUseCase($repo);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error returning coins: Return error');
        $useCase->execute();
    }

    public function testReturnCoinsSuccess(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->createMock(VendingMachine::class);
        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);
        $machine->method('returnInsertedCoins')->willReturn([0.5, 0.5]);
        $machine->method('jsonSerialize')->willReturn([
            'coins' => [1.0],
            'insertedMoney' => 0.0
        ]);

        $useCase = new ReturnCoinsUseCase($repo);
        $result = $useCase->execute();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('returnedCoins', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals([0.5, 0.5], $result['returnedCoins']);
        $this->assertEquals([
            'coins' => [1.0],
            'insertedMoney' => 0.0
        ], $result['status']);
    }
}
