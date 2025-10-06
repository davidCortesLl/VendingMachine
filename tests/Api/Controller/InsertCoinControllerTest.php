<?php

declare(strict_types=1);

namespace Api\Controller;

use Application\UseCase\InsertCoinUseCase;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class InsertCoinControllerTest extends TestCase
{
    private $useCase;
    private $request;
    private $response;

    protected function setUp(): void
    {
        $this->useCase = $this->createMock(InsertCoinUseCase::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
    }

    public function testInvalidJsonBody()
    {
        $this->request->method('getParsedBody')->willReturn(null);
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();

        $controller = new InsertCoinController($this->useCase);
        $controller($this->request, $this->response, []);
        $this->assertStringContainsString('Invalid JSON body', (string)$body);
    }

    public function testMissingValueParameter()
    {
        $this->request->method('getParsedBody')->willReturn([]);
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();

        $controller = new InsertCoinController($this->useCase);
        $controller($this->request, $this->response, []);
        $this->assertStringContainsString('Missing value parameter', (string)$body);
    }

    public function testValueParameterNotNumeric()
    {
        $this->request->method('getParsedBody')->willReturn(['value' => 'abc']);
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();

        $controller = new InsertCoinController($this->useCase);
        $controller($this->request, $this->response, []);
        $this->assertStringContainsString('Value parameter must be numeric', (string)$body);
    }

    public function testSuccess()
    {
        $this->request->method('getParsedBody')->willReturn(['value' => 1.0]);
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();
        $this->useCase->method('execute')->willReturn([
            'coins' => [1.0],
            'insertedMoney' => 1.0
        ]);
        $controller = new InsertCoinController($this->useCase);
        $controller($this->request, $this->response, []);
        $json = json_decode((string)$body, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('status', $json);
        $this->assertEquals([
            'coins' => [1.0],
            'insertedMoney' => 1.0
        ], $json['status']);
    }

    public function testUseCaseThrowsException()
    {
        $this->request->method('getParsedBody')->willReturn(['value' => 1.0]);
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();
        $this->useCase->method('execute')->willThrowException(new \Exception('Test error'));
        $controller = new InsertCoinController($this->useCase);
        $controller($this->request, $this->response, []);
        $this->assertStringContainsString('error', (string)$body);
    }

    private function getMockedBody()
    {
        require_once __DIR__ . '/TestStream.php';
        return new \Tests\Api\Controller\TestStream();
    }
}
