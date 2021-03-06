<?php

require_once 'bootstrap.php';

use atk4\ui\App;

$app = new App([
    'title' => 'Agile UI - DebugBar',
]);

$app->initLayout('Centered');
$app->add($debugBar = new ATK4PHPDebugBar\DebugBar());
$debugBar->setAssetsResourcesUrl('../');
$debugBar->addDefaultCollectors();

$loader = $app->add('Loader');

$loader->set(function ($l) {
    $number = rand(1, 100);
    $l->app->getDebugBarCollector('messages')->addMessage('new message :'.$number);

    $l->add(['Text', 'random :'.$number]);
});

/** @var Button $button */
$button = $app->add(['Button', 'test']);
$button->on('click', function ($j) use ($loader) {
    return $loader->jsReload();
});

$app->run();
