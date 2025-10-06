<?php

declare(strict_types=1);

namespace Api\Controller;

use Application\UseCase\InsertCoinUseCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Domain\Repository\VendingMachineRepository;

class InsertCoinController
{
    public function __construct(
        private readonly VendingMachineRepository $repository
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $validationError = $this->validateInsertCoinRequest($input);
        if ($validationError !== null) {
            $response->getBody()->write(json_encode(['error' => $validationError]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $value = (float)$input['value'];
        $useCase = new InsertCoinUseCase($this->repository);
        try {
            $useCase->execute($value);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(['status' => $this->repository->get()]));

        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    private function validateInsertCoinRequest($input): ?string
    {
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
}

