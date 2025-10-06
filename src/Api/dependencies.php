<?php

use Api\Controller\InsertCoinController;
use Api\Controller\ReturnCoinsController;
use Api\Controller\SelectItemController;
use Api\Controller\ServiceSetMachineController;
use Application\UseCase\InsertCoinUseCase;
use Application\UseCase\ReturnCoinsUseCase;
use Application\UseCase\SelectItemUseCase;
use Application\UseCase\ServiceSetMachineUseCase;
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

    $container->set(SelectItemUseCase::class, function(ContainerInterface $c) {
        return new SelectItemUseCase(
            $c->get(VendingMachineRepository::class)
        );
    });
    $container->set(SelectItemController::class, function(ContainerInterface $c) {
        return new SelectItemController(
            $c->get(SelectItemUseCase::class)
        );
    });
    $container->set(InsertCoinUseCase::class, function(ContainerInterface $c) {
        return new InsertCoinUseCase(
            $c->get(VendingMachineRepository::class)
        );
    });
    $container->set(InsertCoinController::class, function(ContainerInterface $c) {
        return new InsertCoinController(
            $c->get(InsertCoinUseCase::class)
        );
    });
    $container->set(ReturnCoinsUseCase::class, function(ContainerInterface $c) {
        return new ReturnCoinsUseCase(
            $c->get(VendingMachineRepository::class)
        );
    });
    $container->set(ReturnCoinsController::class, function(ContainerInterface $c) {
        return new ReturnCoinsController(
            $c->get(ReturnCoinsUseCase::class)
        );
    });
    $container->set(ServiceSetMachineUseCase::class, function(ContainerInterface $c) {
        return new ServiceSetMachineUseCase(
            $c->get(VendingMachineRepository::class)
        );
    });
    $container->set(ServiceSetMachineController::class, function(ContainerInterface $c) {
        return new ServiceSetMachineController(
            $c->get(ServiceSetMachineUseCase::class)
        );
    });
};
