<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Application\UseCase\ReturnCoinsUseCase;
use Domain\Repository\VendingMachineRepository;
use Domain\Model\VendingMachine;

class ReturnCoinsUseCaseTest extends TestCase
{
    public function testReturnInsertedCoinsSuccess(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->createMock(VendingMachine::class);
        $coins = [(object)['value' => 0.25, 'count' => 2], (object)['value' => 1, 'count' => 1]];
        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);
        $machine->expects($this->once())->method('returnInsertedCoins')->willReturn($coins);
        $useCase = new ReturnCoinsUseCase($repo);
        $result = $useCase->execute();
        $this->assertEquals([
            ['value' => 0.25, 'count' => 2],
            ['value' => 1, 'count' => 1]
        ], $result);
    }

    public function testReturnNoInsertedCoins(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->createMock(VendingMachine::class);
        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);
        $machine->expects($this->once())->method('returnInsertedCoins')->willReturn([]);
        $useCase = new ReturnCoinsUseCase($repo);
        $result = $useCase->execute();
        $this->assertEquals([], $result);
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
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->createMock(VendingMachine::class);
        $repo->method('get')->willReturn($machine);
        $repo->method('save')->willThrowException(new Exception('Save error'));
        $machine->expects($this->once())->method('returnInsertedCoins')->willReturn([]);
        $useCase = new ReturnCoinsUseCase($repo);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error returning coins: Save error');
        $useCase->execute();
    }

    public function testReturnInsertedCoinsThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->createMock(VendingMachine::class);
        $repo->method('get')->willReturn($machine);
        $machine->method('returnInsertedCoins')->willThrowException(new Exception('Return error'));
        $useCase = new ReturnCoinsUseCase($repo);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error returning coins: Return error');
        $useCase->execute();
    }
}

