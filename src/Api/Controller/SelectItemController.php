<?php

declare(strict_types=1);

namespace Api\Controller;

use Application\UseCase\SelectItemUseCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Domain\Repository\VendingMachineRepository;

class SelectItemController
{
    private VendingMachineRepository $repository;

    public function __construct(VendingMachineRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $validationError = $this->validateSelectItemRequest($input);
        if ($validationError !== null) {
            $response->getBody()->write(json_encode(['error' => $validationError]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $selector = $input['selector'];
        $useCase = new SelectItemUseCase($this->repository);
        try {
            $result = $useCase->execute($selector);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode($result));

        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    private function validateSelectItemRequest($input): ?string
    {
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
}

