<?php

declare(strict_types=1);

namespace Infrastructure;

use Domain\Repository\VendingMachineRepository;
use Domain\Model\VendingMachine;

class InMemoryVendingMachineRepository implements VendingMachineRepository {
    public function __construct(
        public VendingMachine $machine
    ) {}
    public function get(): VendingMachine {
        return $this->machine;
    }
    public function save(VendingMachine $machine): void {
        $this->machine = $machine;
    }
}

