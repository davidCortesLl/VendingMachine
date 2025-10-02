<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Repository\VendingMachineRepository;
use Exception;

class InsertCoinUseCase {
    public const VALID_COINS = [0.05, 0.10, 0.25, 1.00];

    public function __construct(
        public VendingMachineRepository $repository
    ) {}

    /**
     * @throws Exception
     */
    public function execute(float $value): bool {
        if (!$this->validateCoin($value)) {
            return false;
        }
        try {
            $machine = $this->repository->get();
            $machine->insertCoin($value);
            $this->repository->save($machine);

            return true;
        }
        catch (Exception $e) {
            throw new Exception("Error inserting coin: " . $e->getMessage());
        }
    }

    function validateCoin(float $value): bool {
        return in_array($value, $this::VALID_COINS, true);
    }
}
