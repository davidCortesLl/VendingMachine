<?php

use Domain\Model\VendingMachine;
use Domain\Model\Item;
use Domain\Repository\VendingMachineRepository;
use Infrastructure\RedisVendingMachineRepository;
use Psr\Container\ContainerInterface;

return function ($container) {
    $container->set(VendingMachineRepository::class, function(ContainerInterface $c) {
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
        $defaultMachine = new VendingMachine($items, $coins);
        return new RedisVendingMachineRepository('redis', 6379, 'vending_machine', $defaultMachine);
    });
};
