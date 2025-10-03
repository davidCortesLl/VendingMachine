<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Repository\VendingMachineRepository;

class ReturnCoinsUseCase {
    public function __construct(
        public VendingMachineRepository $repository) {
    }

    public function execute(): array {
        try {
            $machine = $this->repository->get();
            $coins = $machine->returnInsertedCoins();
            $this->repository->save($machine);
            $result = array_map(fn($coin) => ['value' => $coin->value, 'count' => $coin->count], $coins);

            return $result;
        } catch (\Exception $e) {
            throw new \Exception("Error returning coins: " . $e->getMessage());
        }
    }
}

