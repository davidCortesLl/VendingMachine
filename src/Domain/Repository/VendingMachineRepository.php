<?php

declare(strict_types=1);

namespace Domain\Repository;

use Domain\Model\VendingMachine;

interface VendingMachineRepository {
    public function get(): VendingMachine;
    public function save(VendingMachine $machine): void;
}
