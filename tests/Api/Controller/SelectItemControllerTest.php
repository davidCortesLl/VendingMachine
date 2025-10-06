<?php

declare(strict_types=1);

namespace Api\Controller;

use Application\UseCase\SelectItemUseCase;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class SelectItemControllerTest extends TestCase
{
    private $useCase;
    private $request;
    private $response;

    protected function setUp(): void
    {
        $this->useCase = $this->createMock(SelectItemUseCase::class);
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
        $controller = new SelectItemController($this->useCase);
        $controller($this->request, $this->response, []);
        $this->assertStringContainsString('Invalid JSON body', (string)$body);
    }

    public function testMissingSelectorParameter()
    {
        $this->request->method('getParsedBody')->willReturn([]);
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();
        $controller = new SelectItemController($this->useCase);
        $controller($this->request, $this->response, []);
        $this->assertStringContainsString('Missing selector parameter', (string)$body);
    }

    public function testSelectorParameterNotString()
    {
        $this->request->method('getParsedBody')->willReturn(['selector' => 123]);
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();
        $controller = new SelectItemController($this->useCase);
        $controller($this->request, $this->response, []);
        $this->assertStringContainsString('Selector parameter must be a string', (string)$body);
    }

    public function testUseCaseThrowsException()
    {
        $this->request->method('getParsedBody')->willReturn(['selector' => 'A1']);
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();
        $this->useCase->method('execute')->willThrowException(new \Exception('Test error'));
        $controller = new SelectItemController($this->useCase);
        $controller($this->request, $this->response, []);
        $this->assertStringContainsString('error', (string)$body);
    }

    public function testSuccess()
    {
        $this->request->method('getParsedBody')->willReturn(['selector' => 'A1']);
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();
        $this->useCase->method('execute')->willReturn([
            'selected' => 'Coke',
            'returnedChange' => [0.5],
            'status' => ['coins' => [1.0], 'insertedMoney' => 1.0]
        ]);
        $controller = new SelectItemController($this->useCase);
        $controller($this->request, $this->response, []);
        $json = json_decode((string)$body, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('selected', $json);
        $this->assertArrayHasKey('returnedChange', $json);
        $this->assertArrayHasKey('status', $json);
        $this->assertEquals('Coke', $json['selected']);
        $this->assertEquals([0.5], $json['returnedChange']);
        $this->assertEquals(['coins' => [1.0], 'insertedMoney' => 1.0], $json['status']);
    }

    private function getMockedBody()
    {
        require_once __DIR__ . '/TestStream.php';
        return new \Tests\Api\Controller\TestStream();
    }
}
