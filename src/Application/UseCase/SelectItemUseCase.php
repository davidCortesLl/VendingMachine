<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Repository\VendingMachineRepository;

class SelectItemUseCase {
    public function __construct(
        public VendingMachineRepository $repository
    ) {}
    public function execute() {
        // TODO: select item logic
    }
}
