<?php

require_once 'bootstrap.php';

use Abbadon1334\ATKFastRoute\Handler\RoutedUI;
use Abbadon1334\ATKFastRoute\Router;
use atk4\ui\App;
use atk4\ui\View;

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

class ATKView extends View
{
    public $text;

    public function init(): void
    {
        parent::init();

        $model_user = new User($this->app->db);

        // Migration : if not exists table create it
        \atk4\schema\Migration::getMigration($model_user)->migrate();

        $model_user->insert(['name'=>'test '.rand(0, 800), 'email'=>'test1@test.it']);

        $loader = $this->app->add('Loader');

        $loader->set(function ($l) use ($model_user) {
            $model_user->tryLoadAny();

            $name = $model_user->get('name');
            $l->app->getDebugBarCollector('messages')->addMessage('new message :'.$name);

            $l->add(['Text', 'country name :'.$name]);
        });

        /** @var Button $button */
        $button = $this->app->add(['Button', 'test']);
        $button->on('click', function ($j) use ($loader) {
            return $loader->jsReload();
        });
    }
}

$app = new App([
    'title' => 'Agile UI - DebugBar',
    'db'    => \atk4\data\Persistence::connect('sqlite::memory:'),
    'always_run' => false,
]);

$router = new Router($app);
$app->initLayout('Centered');
$app->add($debugBar = new ATK4PHPDebugBar\DebugBar([
    'open_handler' => '/demos/handler-ajax.php',
]));

$debugBar->addATK4PersistenceSQLCollector();

$router->addRoute(
    '/demos/example-atk4-router.php',
    ['GET', 'POST'],
    new RoutedUI(ATKView::class, [])
);

$router->run();
