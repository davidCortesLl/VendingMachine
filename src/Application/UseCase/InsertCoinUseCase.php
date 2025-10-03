<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Model\Coin;
use Domain\Repository\VendingMachineRepository;
use Exception;

class InsertCoinUseCase {
    public function __construct(
        public VendingMachineRepository $repository
    ) {}

    /**
     * @throws Exception
     */
    public function execute(float $value): void {
        $error = Coin::validateCoinData($value, 1);
        if ($error !== null) {
            throw new \Exception($error);
        }
        try {
            $machine = $this->repository->get();
            $machine->insertCoin($value);
            $this->repository->save($machine);
            return;
        }
        catch (\Exception $e) {
            throw new \Exception("Error inserting coin: " . $e->getMessage());
        }
    }
}
