<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Application\UseCase\InsertCoinUseCase;
use Application\UseCase\ReturnCoinsUseCase;
use Application\UseCase\SelectItemUseCase;
use Application\UseCase\ServiceSetMachineUseCase;

$repository = getVendingMachineRepository();
$persistence = getPersistence();

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && $uri === '/insert-coin') {
    $input = json_decode(file_get_contents('php://input'), true);
    $validationError = validateInsertCoinRequest($input);
    if ($validationError !== null) {
        http_response_code(400);
        echo json_encode(['error' => $validationError]);
        exit;
    }

    $value = (float)$input['value'];
    $useCase = new InsertCoinUseCase($repository);
    try {
        $useCase->execute($value);
    } catch (Exception $e) {
        http_response_code(422);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    $persistence->save($repository->get());
    http_response_code(200);
    echo json_encode(['status' => $repository->get()]);
    exit;
}
if ($method === 'POST' && $uri === '/return-coin') {
    try {
        $useCase = new ReturnCoinsUseCase($repository);
        $returnedCoins = $useCase->execute();
        $persistence->save($repository->get());
    } catch (Exception $e) {
        http_response_code(422);
        echo json_encode(['error' => $e->getMessage()]);

        exit;
    }

    http_response_code(200);
    echo json_encode(['status' => $returnedCoins]);

    exit;
}
if ($method === 'POST' && $uri === '/select-item') {
    $input = json_decode(file_get_contents('php://input'), true);
    $validationError = validateSelectItemRequest($input);
    if ($validationError !== null) {
        http_response_code(400);
        echo json_encode(['error' => $validationError]);

        exit;
    }

    $selector = $input['selector'];
    $useCase = new SelectItemUseCase($repository);
    try {
        $result = $useCase->execute($selector);
    } catch (Exception $e) {
        http_response_code(422);
        echo json_encode(['error' => $e->getMessage()]);

        exit;
    }
    $persistence->save($repository->get());

    http_response_code(200);
    echo json_encode($result);

    exit;
}
if ($method === 'POST' && $uri === '/service/set-machine') {
    $input = json_decode(file_get_contents('php://input'), true);

    $validationError = validateSetMachineRequest($input);
    if ($validationError !== null) {
        http_response_code(400);
        echo json_encode(['error' => $validationError]);
        exit;
    }

    $items = $input['items'];
    $coins = $input['coins'];
    $useCase = new ServiceSetMachineUseCase($repository);
    try {
        $useCase->execute($items, $coins);
    } catch (Exception $e) {
        http_response_code(422);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }

    $persistence->save($repository->get());

    http_response_code(200);
    echo json_encode(['status' => $repository->get()]);

    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Method not found']);

function validateInsertCoinRequest($input): ?string {
    if (!is_array($input)) {
        return 'Invalid JSON body';
    }
    if (!isset($input['value'])) {
        return 'Missing value parameter';
    }
    if (!is_numeric($input['value'])) {
        return 'Value parameter must be numeric';
    }
    return null;
}

function validateSetMachineRequest($input): ?string {
    if (!is_array($input)) {
        return 'Invalid JSON body';
    }
    $items = $input['items'] ?? null;
    $coins = $input['coins'] ?? null;
    if (!is_array($items) || !is_array($coins)) {
        return 'Missing or invalid items or coins array';
    }
    foreach ($items as $i => $item) {
        if (!isset($item['selector'], $item['name'], $item['price'], $item['count'])) {
            return "Item in position $i incomplete: selector, name, price, count are required";
        }
        if (!is_string($item['selector']) || !is_string($item['name']) || !is_numeric($item['price']) || !is_int($item['count'])) {
            return "Item in position $i has invalid types";
        }
    }
    foreach ($coins as $i => $coin) {
        if (!isset($coin['value'], $coin['count'])) {
            return "Coin in position $i incomplete: value and count are required";
        }
        if (!is_numeric($coin['value']) || !is_int($coin['count'])) {
            return "Coin in position $i has invalid types";
        }
    }
    return null;
}

function validateSelectItemRequest($input): ?string {
    if (!is_array($input)) {
        return 'Invalid JSON body';
    }
    if (!isset($input['selector'])) {
        return 'Missing selector parameter';
    }
    if (!is_string($input['selector'])) {
        return 'Selector parameter must be a string';
    }
    return null;
}
