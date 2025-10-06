<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Repository\VendingMachineRepository;

class ReturnCoinsUseCase {
    public function __construct(
        public VendingMachineRepository $repository) {
    }

    /**
     * @throws \Exception
     */
    public function execute(): array {
        try {
            $machine = $this->repository->get();
            $coins = $machine->returnInsertedCoins();
            $this->repository->save($machine);

            return [
                'returnedCoins' => $coins,
                'status' => $machine->jsonSerialize()
            ];
        } catch (\Exception $e) {
            throw new \Exception("Error returning coins: " . $e->getMessage());
        }
    }
}
