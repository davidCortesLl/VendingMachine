<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Repository\VendingMachineRepository;
use Exception;

class ServiceSetMachineUseCase {
    public function __construct(
        public VendingMachineRepository $repository
    ) {}

    /**
     * @throws Exception
     */
    public function execute(array $itemsData, array $coinsData): array {
        try {
            $machine = $this->repository->get();
            $machine->serviceSetMachine($itemsData, $coinsData);
            $this->repository->save($machine);

            return $machine->jsonSerialize();
        } catch (\Exception $e) {
            throw new Exception("Error setting machine configuration: " . $e->getMessage());
        }
    }
}
