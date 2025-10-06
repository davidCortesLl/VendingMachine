<?php

declare(strict_types=1);

namespace Api\Controller;

use Application\UseCase\ReturnCoinsUseCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Domain\Repository\VendingMachineRepository;

readonly class ReturnCoinsController
{
    public function __construct(
        private VendingMachineRepository $repository
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        try {
            $useCase = new ReturnCoinsUseCase($this->repository);
            $returnedCoins = $useCase->execute();
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode([
            'returnedCoins' => $returnedCoins,
            'status' => $this->repository->get()
        ]));

        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }
}

