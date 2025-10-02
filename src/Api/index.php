<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Application\UseCase\InsertCoinUseCase;
use Application\UseCase\ReturnCoinUseCase;
use Application\UseCase\SelectItemUseCase;
use Application\UseCase\ServiceSetMachineUseCase;
use Infrastructure\InMemoryVendingMachineRepository;
use Infrastructure\RedisVendingMachinePersistence;
use Domain\Model\VendingMachine;
use Domain\Model\Item;
use Domain\Model\Coin;

$defaultMachine = getDefaultMachineState();
$persistence = new RedisVendingMachinePersistence('redis', 6379, 'vending_machine');
$machine = $persistence->load($defaultMachine);
$repository = new InMemoryVendingMachineRepository($machine);

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && $uri === '/insert-coin') {
    $input = json_decode(file_get_contents('php://input'), true);
    $value = isset($input['value']) ? (float)$input['value'] : null;
    if ($value === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid value parameter']);
        exit;
    }

    $useCase = new InsertCoinUseCase($repository);
    $useCase->execute($value);
    $persistence->save($repository->get());

    http_response_code(200);
    echo json_encode(['status' => $repository->get()]);

    exit;
}
if ($method === 'POST' && $uri === '/return-coin') {
    $useCase = new ReturnCoinUseCase($repository);
    $useCase->execute();
    $persistence->save($repository->get());
    http_response_code(200);
    echo json_encode(['status' => 'ReturnCoin endpoint called']);
    exit;
}
if ($method === 'POST' && $uri === '/select-item') {
    $useCase = new SelectItemUseCase($repository);
    $useCase->execute();
    $persistence->save($repository->get());
    http_response_code(200);
    echo json_encode(['status' => 'SelectItem endpoint called']);
    exit;
}
if ($method === 'POST' && $uri === '/service/set-machine') {
    $useCase = new ServiceSetMachineUseCase($repository);
    $useCase->execute();
    $persistence->save($repository->get());
    http_response_code(200);
    echo json_encode(['status' => 'ServiceSetMachine endpoint called']);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);

function getDefaultMachineState(): VendingMachine {
    $items = [
        new Item('1', 'Water', 0.65, 5),
        new Item('2', 'Juice', 1.00, 5),
        new Item('3', 'Soda', 1.50, 5),
    ];
    $coins = [
        new Coin(0.05, 10),
        new Coin(0.10, 10),
        new Coin(0.25, 10),
        new Coin(1.00, 10),
    ];
    return new VendingMachine($items, $coins);
}
