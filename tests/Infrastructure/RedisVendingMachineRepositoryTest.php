<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use Domain\Model\VendingMachine;
use Infrastructure\RedisVendingMachineRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RedisVendingMachineRepositoryTest extends TestCase
{
    private MockObject $redisMock;
    private RedisVendingMachineRepository $repository;
    private VendingMachine $default;

    protected function setUp(): void
    {
        $this->redisMock = $this->createMock(\Redis::class);
        $this->default = new VendingMachine([], []);
        $this->repository = new RedisVendingMachineRepository('dummy', 1234, 'vending_machine', $this->default, $this->redisMock);
    }

    public function testGetReturnsSavedVendingMachine()
    {
        $machine = new VendingMachine([], []);
        $this->redisMock->method('get')->willReturn(serialize($machine));
        $result = $this->repository->get();
        $this->assertInstanceOf(VendingMachine::class, $result);
    }

    public function testGetReturnsDefaultIfNoData()
    {
        $this->redisMock->method('get')->willReturn(false);
        $result = $this->repository->get();
        $this->assertSame($this->default, $result);
    }

    public function testGetReturnsDefaultIfDataIsInvalid()
    {
        $this->redisMock->method('get')->willReturn(serialize(new \stdClass()));
        $result = $this->repository->get();
        $this->assertSame($this->default, $result);
    }

    public function testSaveStoresSerializedVendingMachine()
    {
        $machine = new VendingMachine([], []);
        $this->redisMock->expects($this->once())
            ->method('set')
            ->with('vending_machine', $this->callback(function($data) use ($machine) {
                $unserialized = unserialize($data);
                return $unserialized instanceof VendingMachine;
            }));
        $this->repository->save($machine);
    }
}
