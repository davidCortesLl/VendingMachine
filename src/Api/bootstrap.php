<?php

use Domain\Model\VendingMachine;
use Domain\Model\Item;
use Domain\Repository\VendingMachineRepository;
use Infrastructure\RedisVendingMachineRepository;

require_once __DIR__ . '/../../vendor/autoload.php';

function getDefaultMachineState(): VendingMachine {
    $items = [
        new Item('1', 'Water', 0.65, 5),
        new Item('2', 'Juice', 1.00, 5),
        new Item('3', 'Soda', 1.50, 5),
    ];
    $coins = [
        "0.05" => 10,
        "0.10" => 10,
        "0.25" => 10,
        "1.00" => 10,
    ];

    return new VendingMachine($items, $coins);
}

function getVendingMachineRepository(): VendingMachineRepository {
    $defaultMachine = getDefaultMachineState();

    return new RedisVendingMachineRepository('redis', 6379, 'vending_machine', $defaultMachine);
}
