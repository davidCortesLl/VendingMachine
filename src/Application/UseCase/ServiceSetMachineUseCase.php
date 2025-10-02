<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Repository\VendingMachineRepository;

class ServiceSetMachineUseCase {
    public function __construct(
        public VendingMachineRepository $repository
    ) {}
    public function execute() {
        // TODO: set machine service logic
    }
}
