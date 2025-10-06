<?php

declare(strict_types=1);

namespace Api\Controller;

use Application\UseCase\ServiceSetMachineUseCase;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ServiceSetMachineControllerTest extends TestCase
{
    private $useCase;
    private $request;
    private $response;

    protected function setUp(): void
    {
        $this->useCase = $this->createMock(ServiceSetMachineUseCase::class);
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
        $controller = new ServiceSetMachineController($this->useCase);
        $controller($this->request, $this->response, []);
        $this->assertStringContainsString('Invalid JSON body', (string)$body);
    }

    public function testMissingOrInvalidItemsOrCoins()
    {
        $this->request->method('getParsedBody')->willReturn(['items' => null, 'coins' => null]);
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();
        $controller = new ServiceSetMachineController($this->useCase);
        $controller($this->request, $this->response, []);
        $this->assertStringContainsString('Missing or invalid items or coins array', (string)$body);
    }

    public function testUseCaseThrowsException()
    {
        $this->request->method('getParsedBody')->willReturn(['items' => [], 'coins' => []]);
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();
        $this->useCase->method('execute')->willThrowException(new \Exception('Test error'));
        $controller = new ServiceSetMachineController($this->useCase);
        $controller($this->request, $this->response, []);
        $this->assertStringContainsString('error', (string)$body);
    }

    public function testSuccess()
    {
        $this->request->method('getParsedBody')->willReturn(['items' => [], 'coins' => []]);
        $body = $this->getMockedBody();
        $this->response->method('getBody')->willReturn($body);
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();
        $this->useCase->method('execute')->willReturn([
            'coins' => [1.0],
            'insertedMoney' => 1.0
        ]);
        $controller = new ServiceSetMachineController($this->useCase);
        $controller($this->request, $this->response, []);
        $json = json_decode((string)$body, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('status', $json);
        $this->assertEquals([
            'coins' => [1.0],
            'insertedMoney' => 1.0
        ], $json['status']);
    }

    public function testItemSelectorEmpty()
    {
        $input = [
            'items' => [[
                'selector' => '',
                'name' => 'Snack',
                'price' => 1.0,
                'count' => 1
            ]],
            'coins' => [[
                'value' => 1.0,
                'count' => 1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result, 'La validación debería devolver un mensaje de error, pero devolvió null');
        $this->assertStringContainsString('selector and name cannot be empty', $result);
    }

    public function testItemNameEmpty()
    {
        $input = [
            'items' => [[
                'selector' => 'A1',
                'name' => '',
                'price' => 1.0,
                'count' => 1
            ]],
            'coins' => [[
                'value' => 1.0,
                'count' => 1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result, 'La validación debería devolver un mensaje de error, pero devolvió null');
        $this->assertStringContainsString('selector and name cannot be empty', $result);
    }

    public function testItemPriceNegative()
    {
        $input = [
            'items' => [[
                'selector' => 'A1',
                'name' => 'Snack',
                'price' => -1.0,
                'count' => 1
            ]],
            'coins' => [[
                'value' => 1.0,
                'count' => 1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result, 'La validación debería devolver un mensaje de error, pero devolvió null');
        $this->assertStringContainsString('price must be >= 0', $result);
    }

    public function testItemCountNegative()
    {
        $input = [
            'items' => [[
                'selector' => 'A1',
                'name' => 'Snack',
                'price' => 1.0,
                'count' => -1
            ]],
            'coins' => [[
                'value' => 1.0,
                'count' => 1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result, 'La validación debería devolver un mensaje de error, pero devolvió null');
        $this->assertStringContainsString('count must be >= 0', $result);
    }

    public function testCoinValueZeroOrNegative()
    {
        $inputZero = [
            'items' => [[
                'selector' => 'A1',
                'name' => 'Snack',
                'price' => 1.0,
                'count' => 1
            ]],
            'coins' => [[
                'value' => 0,
                'count' => 1
            ]]
        ];
        $inputNegative = [
            'items' => [[
                'selector' => 'A1',
                'name' => 'Snack',
                'price' => 1.0,
                'count' => 1
            ]],
            'coins' => [[
                'value' => -1,
                'count' => 1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $resultZero = $method->invoke($controller, $inputZero);
        $resultNegative = $method->invoke($controller, $inputNegative);
        $this->assertNotNull($resultZero, 'La validación debería devolver un mensaje de error, pero devolvió null');
        $this->assertStringContainsString('value must be > 0', $resultZero);
        $this->assertNotNull($resultNegative, 'La validación debería devolver un mensaje de error, pero devolvió null');
        $this->assertStringContainsString('value must be > 0', $resultNegative);
    }

    public function testCoinCountNegative()
    {
        $input = [
            'items' => [[
                'selector' => 'A1',
                'name' => 'Snack',
                'price' => 1.0,
                'count' => 1
            ]],
            'coins' => [[
                'value' => 1.0,
                'count' => -1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result, 'La validación debería devolver un mensaje de error, pero devolvió null');
        $this->assertStringContainsString('count must be >= 0', $result);
    }

    public function testItemSelectorNotString()
    {
        $input = [
            'items' => [[
                'selector' => 123,
                'name' => 'Snack',
                'price' => 1.0,
                'count' => 1
            ]],
            'coins' => [[
                'value' => 1.0,
                'count' => 1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result);
        $this->assertStringContainsString('invalid types', $result);
    }

    public function testItemNameNotString()
    {
        $input = [
            'items' => [[
                'selector' => 'A1',
                'name' => 123,
                'price' => 1.0,
                'count' => 1
            ]],
            'coins' => [[
                'value' => 1.0,
                'count' => 1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result);
        $this->assertStringContainsString('invalid types', $result);
    }

    public function testItemPriceNotNumeric()
    {
        $input = [
            'items' => [[
                'selector' => 'A1',
                'name' => 'Snack',
                'price' => 'no-num',
                'count' => 1
            ]],
            'coins' => [[
                'value' => 1.0,
                'count' => 1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result);
        $this->assertStringContainsString('invalid types', $result);
    }

    public function testItemCountNotInt()
    {
        $input = [
            'items' => [[
                'selector' => 'A1',
                'name' => 'Snack',
                'price' => 1.0,
                'count' => 'no-int'
            ]],
            'coins' => [[
                'value' => 1.0,
                'count' => 1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result);
        $this->assertStringContainsString('invalid types', $result);
    }

    public function testCoinMissingValue()
    {
        $input = [
            'items' => [[
                'selector' => 'A1',
                'name' => 'Snack',
                'price' => 1.0,
                'count' => 1
            ]],
            'coins' => [[
                // 'value' => 1.0,
                'count' => 1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result);
        $this->assertStringContainsString('incomplete', $result);
    }

    public function testCoinMissingCount()
    {
        $input = [
            'items' => [[
                'selector' => 'A1',
                'name' => 'Snack',
                'price' => 1.0,
                'count' => 1
            ]],
            'coins' => [[
                'value' => 1.0
                // 'count' => 1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result);
        $this->assertStringContainsString('incomplete', $result);
    }

    public function testCoinValueNotNumeric()
    {
        $input = [
            'items' => [[
                'selector' => 'A1',
                'name' => 'Snack',
                'price' => 1.0,
                'count' => 1
            ]],
            'coins' => [[
                'value' => 'no-num',
                'count' => 1
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result);
        $this->assertStringContainsString('invalid types', $result);
    }

    public function testCoinCountNotInt()
    {
        $input = [
            'items' => [[
                'selector' => 'A1',
                'name' => 'Snack',
                'price' => 1.0,
                'count' => 1
            ]],
            'coins' => [[
                'value' => 1.0,
                'count' => 'no-int'
            ]]
        ];
        $controller = new ServiceSetMachineController($this->useCase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateSetMachineRequest');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $input);
        $this->assertNotNull($result);
        $this->assertStringContainsString('invalid types', $result);
    }

    private function getMockedBody()
    {
        require_once __DIR__ . '/TestStream.php';
        return new \Tests\Api\Controller\TestStream();
    }
}
