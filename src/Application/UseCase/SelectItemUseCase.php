<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Repository\VendingMachineRepository;
use Exception;

class SelectItemUseCase {
    public function __construct(
        public VendingMachineRepository $repository
    ) {}

    /**
     * @throws Exception
     */
    public function execute(string $selector): array {
        try {
            $machine = $this->repository->get();
            $result = $machine->selectItem($selector);
            $this->repository->save($machine);

            return [
                'selected' => $result['item'] ?? null,
                'returnedChange' => $result['change'] ?? [],
                'status' => $machine->jsonSerialize()
            ];
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
