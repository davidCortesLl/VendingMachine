<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Repository\VendingMachineRepository;
use Domain\Model\Coin;
use Exception;

class SelectItemUseCase {
    public function __construct(
        public VendingMachineRepository $repository
    ) {}

    /**
     * @param string $selector
     * @return array
     * @throws Exception
     */
    public function execute(string $selector): array {
        $machine = $this->repository->get();

        // Find item
        $item = null;
        foreach ($machine->items as $i) {
            if ($i->selector === $selector) {
                $item = $i;
                break;
            }
        }
        if (!$item) {
            throw new Exception("Item not found");
        }
        if ($item->count < 1) {
            throw new Exception("Item out of stock");
        }

        // Calculate inserted money
        $inserted = 0.0;
        foreach ($machine->insertedMoney as $coin) {
            $inserted += $coin->value * $coin->count;
        }
        if ($inserted < $item->price) {
            throw new Exception("Insufficient funds. Inserted: $inserted, Price: $item->price");
        }
        $change = round($inserted - $item->price, 2);

        // Calculate change to return
        [$changeCoins, $remainingChange] = $this->getChangeFromInsertedMoney($change, $machine);
        if ($remainingChange > 0.009) {
            $this->getChangeFromMachineCoins($remainingChange, $machine, $changeCoins);

            foreach ($machine->insertedMoney as $insertedCoin) {
                $machine->addCoin($insertedCoin->value);
            }
        }

        $item->count -= 1;
        $machine->insertedMoney = [];
        $this->repository->save($machine);

        return [
            'item' => [
                'selector' => $item->selector,
                'name' => $item->name,
                'price' => $item->price
            ],
            'change' => $changeCoins,
            'status' => $machine
        ];
    }

    /**
     * Tries to get change from inserted money first
     * @return array [changeCoins, remainingChange]
     */
    private function getChangeFromInsertedMoney(float $change, $machine): array {
        $insertedChange = $this->calculateChange($change, $machine->insertedMoney);
        $remainingChange = $change;
        $changeCoins = [];
        if ($change > 0 && $insertedChange !== null) {
            foreach ($insertedChange as $coinValue => $coinCount) {
                $remainingChange = round($remainingChange - ($coinValue * $coinCount), 2);
                $changeCoins[$coinValue] = $coinCount;
                foreach ($machine->insertedMoney as $coin) {
                    if ($coin->value == $coinValue) {
                        $coin->count -= $coinCount;
                    }
                }
            }
        }

        if ($machine->isInsertedMoneyEmpty()) {
            $machine->insertedMoney = [];
        }

        return [$changeCoins, $remainingChange];
    }

    /**
     * If there is still remaining change, tries to get it from machine coins
     * @throws Exception
     */
    private function getChangeFromMachineCoins(float $remainingChange, $machine, array &$changeCoins): void {
        $machineChange = $this->calculateChange($remainingChange, $machine->coins);
        if ($machineChange === null) {
            throw new Exception("Cannot provide exact change");
        }
        foreach ($machineChange as $coinValue => $coinCount) {
            $changeCoins[$coinValue] = ($changeCoins[$coinValue] ?? 0) + $coinCount;
            foreach ($machine->coins as $coin) {
                if ($coin->value == $coinValue) {
                    $coin->count -= $coinCount;
                }
            }
        }
    }

    /**
     * @param float $change
     * @param Coin[] $coins
     * @return array|null
     */
    private function calculateChange(float $change, array $coins): ?array {
        if ($change < 0.01) {
            return [];
        }

        // Work on a copy of the coins array to avoid modifying the original
        $coinsCopy = [];
        foreach ($coins as $coin) {
            $coinsCopy[] = clone $coin;
        }
        usort($coinsCopy, fn($a, $b) => $b->value <=> $a->value);
        $result = [];
        foreach ($coinsCopy as $coin) {
            $count = 0;
            while ($change >= $coin->value - 0.001 && $coin->count > 0) {
                $change = round($change - $coin->value, 2);
                $coin->count--;
                $count++;
            }
            if ($count > 0) {
                $result[(string)$coin->value] = $count;
            }
        }
        if ($change > 0.009) {
            return null;
        }

        return $result;
    }
}
