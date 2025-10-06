<?php

declare(strict_types=1);

namespace Api\Controller;

use Application\UseCase\ReturnCoinsUseCase;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ReturnCoinsControllerTest extends TestCase
{
    private $useCase;
    private $request;
    private $response;

    protected function setUp(): void
    {
        $this->useCase = $this->createMock(ReturnCoinsUseCase::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
    }

    public function testSuccess()
    {
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();
        $this->useCase->method('execute')->willReturn([
            'returnedCoins' => [0.5, 0.5],
            'status' => ['coins' => [1.0], 'insertedMoney' => 1.0]
        ]);
        $controller = new ReturnCoinsController($this->useCase);
        $controller($this->request, $this->response, []);
        $json = json_decode((string)$body, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('returnedCoins', $json);
        $this->assertArrayHasKey('status', $json);
        $this->assertEquals([0.5, 0.5], $json['returnedCoins']);
        $this->assertEquals(['coins' => [1.0], 'insertedMoney' => 1.0], $json['status']);
    }

    public function testUseCaseThrowsException()
    {
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();
        $this->useCase->method('execute')->willThrowException(new \Exception('Test error'));
        $controller = new ReturnCoinsController($this->useCase);
        $controller($this->request, $this->response, []);
        $this->assertStringContainsString('error', (string)$body);
    }

    private function getMockedBody()
    {
        require_once __DIR__ . '/TestStream.php';
        return new \Tests\Api\Controller\TestStream();
    }
}
