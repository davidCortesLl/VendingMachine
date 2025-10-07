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

        $machine->expects($this->once())
            ->method('serviceSetMachine')
            ->with([], []);

        $machine->method('jsonSerialize')->willReturn([
            'items' => [],
            'coins' => [],
            'insertedMoney' => 0.0
        ]);

        $useCase = new ServiceSetMachineUseCase($repo);
        $result = $useCase->execute([], []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('coins', $result);
        $this->assertArrayHasKey('insertedMoney', $result);
    }

    public function testSetMachineWithValidData(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->createMock(VendingMachine::class);

        $itemsData = [
            ['selector' => '1', 'name' => 'Water', 'price' => 0.65, 'count' => 5],
        ];
        $coinsData = [
            ['value' => 0.25, 'count' => 10],
        ];

        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);

        $machine->expects($this->once())
            ->method('serviceSetMachine')
            ->with($itemsData, $coinsData);

        $machine->method('jsonSerialize')->willReturn([
            'items' => $itemsData,
            'coins' => ['0.25' => 10],
            'insertedMoney' => 0.0
        ]);

        $useCase = new ServiceSetMachineUseCase($repo);
        $result = $useCase->execute($itemsData, $coinsData);

        $this->assertIsArray($result);
    }

    public function testSetMachineVendingMachineThrowsValidationError(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->createMock(VendingMachine::class);

        $itemsData = [
            ['selector' => '1', 'name' => '', 'price' => 1.0, 'count' => 5], // Invalid name
        ];
        $coinsData = [
            ['value' => 0.25, 'count' => 10],
        ];

        $repo->method('get')->willReturn($machine);

        // El modelo debe lanzar la excepción de validación
        $machine->expects($this->once())
            ->method('serviceSetMachine')
            ->with($itemsData, $coinsData)
            ->willThrowException(new \Exception('Item name cannot be empty'));

        $useCase = new ServiceSetMachineUseCase($repo);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error setting machine configuration: Item name cannot be empty');

        $useCase->execute($itemsData, $coinsData);
    }

    public function testSetMachineRepositoryGetThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $repo->method('get')->willThrowException(new Exception('Repository error'));

        $useCase = new ServiceSetMachineUseCase($repo);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error setting machine configuration: Repository error');

        $useCase->execute([], []);
    }

    public function testSetMachineRepositorySaveThrows(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->createMock(VendingMachine::class);

        $repo->method('get')->willReturn($machine);
        $repo->method('save')->willThrowException(new Exception('Save error'));

        $machine->expects($this->once())
            ->method('serviceSetMachine')
            ->with([], []);

        $useCase = new ServiceSetMachineUseCase($repo);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error setting machine configuration: Save error');

        $useCase->execute([], []);
    }

    public function testSetMachinePassesCorrectParametersToModel(): void
    {
        $repo = $this->createMock(VendingMachineRepository::class);
        $machine = $this->createMock(VendingMachine::class);

        $itemsData = [
            ['selector' => 'A1', 'name' => 'Water', 'price' => 0.65, 'count' => 5],
            ['selector' => 'B1', 'name' => 'Juice', 'price' => 1.00, 'count' => 3]
        ];

        $coinsData = [
            ['value' => 0.25, 'count' => 10],
            ['value' => 1.00, 'count' => 5]
        ];

        $repo->method('get')->willReturn($machine);
        $repo->expects($this->once())->method('save')->with($machine);

        // Verificar que se pasan exactamente los parámetros correctos
        $machine->expects($this->once())
            ->method('serviceSetMachine')
            ->with(
                $this->equalTo($itemsData),
                $this->equalTo($coinsData)
            );

        $machine->method('jsonSerialize')->willReturn([]);

        $useCase = new ServiceSetMachineUseCase($repo);
        $useCase->execute($itemsData, $coinsData);
    }
}
