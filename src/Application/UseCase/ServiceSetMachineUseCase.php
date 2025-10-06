<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Model\VendingMachine;
use Domain\Repository\VendingMachineRepository;
use Domain\Model\Item;
use Exception;

class ServiceSetMachineUseCase {
    public function __construct(
        public VendingMachineRepository $repository
    ) {}

    /**
     * @throws Exception
     */
    public function execute(array $itemsData, array $coinsData): void {
        $items = [];
        foreach ($itemsData as $item) {
            $error = Item::validateItemData(
                $item['name'],
                (float)$item['price'],
                (int)$item['count']
            );
            if ($error !== null) {
                throw new Exception($error);
            }

            $items[] = new Item(
                $item['selector'],
                $item['name'],
                (float)$item['price'],
                (int)$item['count']
            );
        }

        $coins = [];
        foreach ($coinsData as $coin) {
            $error = VendingMachine::validateCoinData((float)$coin['value'], (int)$coin['count']);
            if ($error !== null) {
                throw new Exception($error);
            }
            $coins[(string)$coin['value']] = (int)$coin['count'];
        }

        $machine = $this->repository->get();
        $machine->items = $items;
        $machine->coins = $coins;
        $this->repository->save($machine);
    }

}
