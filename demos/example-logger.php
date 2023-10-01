<?php

require_once 'bootstrap.php';

use atk4\ui\App;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$monolog = new \Monolog\Logger('atk4');
$monolog->pushHandler(new StreamHandler(__DIR__.'/test.log', Logger::DEBUG));

$app = new App([
    'title' => 'Agile UI - DebugBar',
    'logger'=> $monolog,
]);

$app->initLayout('Centered');
$app->add($debugBar = new ATK4PHPDebugBar\DebugBar());
$debugBar->setAssetsResourcesUrl('../');
//$debugBar->addDefaultCollectors();
$debugBar->addATK4LoggerCollector();

$app->getDebugBarCollector('app')->addMessage('first message');

$loader = $app->add('Loader');

$loader->set(function ($l) {
    $number = rand(1, 100);
    $l->app->getDebugBarCollector('messages')->addMessage('new message :'.$number);

    $l->add(['Text', 'random :'.$number]);

    $l->app->logger->debug('new message :'.$number);
});

/** @var Button $button */
$button = $app->add(['Button', 'test']);
$button->on('click', function ($j) use ($loader) {
    return $loader->jsReload();
});

$app->run();
