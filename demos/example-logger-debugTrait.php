<?php

include 'bootstrap.php';

use atk4\ui\App;

class AppDebug extends App
{
    use \atk4\core\DebugTrait;
}

$app = new AppDebug([
    'title' => 'Agile UI - DebugBar',
    'debug' => true,
]);

$app->initLayout('Centered');
$app->add($debugBar = new ATK4PHPDebugBar\DebugBar());
$debugBar->setAssetsResourcesUrl('../');
$debugBar->addATK4LoggerCollector();

$loader = $app->add('Loader');

$loader->set(function ($l) {
    $number = rand(1, 100);
    $l->app->getDebugBarCollector('messages')->addMessage('new message :'.$number);

    $l->add(['Text', 'random :'.$number]);

    $l->app->debug('debug trait :'.$number);
});

/** @var Button $button */
$button = $app->add(['Button', 'test']);
$button->on('click', function ($j) use ($loader) {
    return $loader->jsReload();
});

$app->run();
