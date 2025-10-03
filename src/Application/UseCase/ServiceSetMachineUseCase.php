<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Repository\VendingMachineRepository;
use Domain\Model\Item;
use Domain\Model\Coin;
use Exception;

class ServiceSetMachineUseCase {
    public function __construct(
        public VendingMachineRepository $repository
    ) {}

    /**
     * @param array $itemsData
     * @param array $coinsData
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
            $error = Coin::validateCoinData((float)$coin['value'], (int)$coin['count']);
            if ($error !== null) {
                throw new Exception($error);
            }

            $coins[] = new Coin(
                (float)$coin['value'],
                (int)$coin['count']
            );
        }

        $machine = $this->repository->get();
        $machine->items = $items;
        $machine->coins = $coins;
        $this->repository->save($machine);

        return;
    }

}
