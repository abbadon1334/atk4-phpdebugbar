<?php

require_once 'bootstrap.php';

use atk4\ui\App;
use ATK4PHPDebugBar\DebugBar;

class User extends \atk4\data\Model
{
    public $table = 'user';

    public function init()
    {
        parent::init();
        $this->addField('name');
        $this->addField('email');
    }
}

// start DbConnection
$db = \atk4\data\Persistence::connect('sqlite::memory:');

$app = new App([
    'title' => 'Agile UI - DebugBar',
    'db'    => $db
]);

$app->initLayout('Centered');
$app->add($debugBar = new ATK4PHPDebugBar\DebugBar([
    'open_handler' => '/demos/handler-ajax.php'
]));

$debugBar->setAssetsResourcesUrl('../');
//$debugBar->addDefaultCollectors();

$debugBar->addATK4PersistenceSQLCollector();

$model_user = new User($app->db);

// Migration : if not exists table create it
\atk4\schema\Migration::getMigration($model_user)->migrate();

$model_user->insert(['name'=>'test 1', 'email'=>'test1@test.it']);
$model_user->insert(['name'=>'test 2', 'email'=>'test2@test.it']);

$loader = $app->add('Loader');

$loader->set(function ($l) use ($model_user) {
    $model_user->tryLoadAny();

    $name = $model_user->get('name');
    $l->app->getDebugBarCollector('messages')->addMessage('new message :'.$name);

    $l->add(['Text', 'country name :'.$name]);
});

/** @var Button $button */
$button = $app->add(['Button', 'test']);
$button->on('click', function ($j) use ($loader) {
    return $loader->jsReload();
});

$app->run();