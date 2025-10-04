<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Application\UseCase\InsertCoinUseCase;
use Domain\Repository\VendingMachineRepository;
use Domain\Model\VendingMachine;

class InsertCoinUseCaseTest extends TestCase
{
    public function testInsertValidCoin(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->createMock(VendingMachine::class);

        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);
        $machine->expects($this->once())->method('insertCoin')->with(0.25);

        $useCase = new InsertCoinUseCase($repo);
        $useCase->execute(0.25);
    }

    public function testInsertInvalidCoinThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);

        $this->expectException(Exception::class);

        $useCase = new InsertCoinUseCase($repo);
        $useCase->execute(0.03);
    }

    public function testInsertNegativeCoinThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);

        $this->expectException(Exception::class);

        $useCase = new InsertCoinUseCase($repo);
        $useCase->execute(-1);
    }

    public function testRepositoryGetThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willThrowException(new Exception('Repo error'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error inserting coin: Repo error');

        $useCase = new InsertCoinUseCase($repo);
        $useCase->execute(0.25);
    }

    public function testRepositorySaveThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->createMock(VendingMachine::class);

        $repo->method('get')->willReturn($machine);
        $repo->method('save')->willThrowException(new Exception('Save error'));
        $machine->expects($this->once())->method('insertCoin')->with(0.25);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error inserting coin: Save error');

        $useCase = new InsertCoinUseCase($repo);
        $useCase->execute(0.25);
    }
}

