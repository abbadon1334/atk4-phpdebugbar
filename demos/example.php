<?php

ini_set('display_errors',true);

include 'bootstrap.php';

use atk4\ui\App;
/*
try {
    $db = new \atk4\data\Persistence\SQL('mysql:dbname=atk4;host=localhost', 'root', 'root');
} catch (\PDOException $e) {
    throw new \atk4\ui\Exception([
        'This demo requires access to the database. See "demos/database.php"',
    ], NULL, $e);
}
*/
$app = new App([
    'title' => 'Agile UI - DebugBar',
//    'db'    => $db
]);

$app->initLayout(\atk4\ui\Layout\Centered::class);
$app->add($debugBar = new ATK4PHPDebugBar\DebugBar());
$debugBar->setAssetsResourcesUrl('../');
$debugBar->addDefaultCollectors();

$loader = $app->add('Loader');

$loader->set(function ($l) {

    $number = rand(1, 100);
    $l->app->getDebugBarCollector('messages')->addMessage('new message :' . $number);

    $l->add(['Text', 'random :' . $number]);
});
/** @var Button $button */
$button = $app->add(['Button', 'test']);
$button->on('click', function ($j) use ($loader) {
    return $loader->jsReload();
});

$app->run();