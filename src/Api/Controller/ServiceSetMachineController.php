<?php

declare(strict_types=1);

namespace Api\Controller;

use Application\UseCase\ServiceSetMachineUseCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ServiceSetMachineController
{
    public function __construct(
        private ServiceSetMachineUseCase $useCase
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $validationError = $this->validateSetMachineRequest($input);
        if ($validationError !== null) {
            $response->getBody()->write(json_encode(['error' => $validationError]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $items = $input['items'];
        $coins = $input['coins'];
        try {
            $status = $this->useCase->execute($items, $coins);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(['status' => $status]));

        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    private function validateSetMachineRequest($input): ?string
    {
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
            if (trim($item['selector']) === '' || trim($item['name']) === '') {
                return "Item in position $i: selector and name cannot be empty";
            }
            if ($item['price'] < 0) {
                return "Item in position $i: price must be >= 0";
            }
            if ($item['count'] < 0) {
                return "Item in position $i: count must be >= 0";
            }
        }

        foreach ($coins as $i => $coin) {
            if (!isset($coin['value'], $coin['count'])) {
                return "Coin in position $i incomplete: value and count are required";
            }
            if (!is_numeric($coin['value']) || !is_int($coin['count'])) {
                return "Coin in position $i has invalid types";
            }
            if ($coin['value'] <= 0) {
                return "Coin in position $i: value must be > 0";
            }
            if ($coin['count'] < 0) {
                return "Coin in position $i: count must be >= 0";
            }
        }

        return null;
    }
}
