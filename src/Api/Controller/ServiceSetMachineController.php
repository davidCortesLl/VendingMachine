<?php

declare(strict_types=1);

namespace Api\Controller;

use Application\UseCase\ServiceSetMachineUseCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Domain\Repository\VendingMachineRepository;

class ServiceSetMachineController
{
    public function __construct(
        private VendingMachineRepository $repository
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
        $useCase = new ServiceSetMachineUseCase($this->repository);
        try {
            $useCase->execute($items, $coins);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(['status' => $this->repository->get()]));

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
}

