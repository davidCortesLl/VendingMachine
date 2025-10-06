<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Api\Controller\InsertCoinController;
use Api\Controller\ReturnCoinsController;
use Api\Controller\SelectItemController;
use Api\Controller\ServiceSetMachineController;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

(require __DIR__ . '/../src/Api/dependencies.php')($container);

// Register routes
$app->post('/insert-coin', InsertCoinController::class);
$app->post('/return-coin', ReturnCoinsController::class);
$app->post('/select-item', SelectItemController::class);
$app->post('/service/set-machine', ServiceSetMachineController::class);

$app->addBodyParsingMiddleware();

$app->run();
