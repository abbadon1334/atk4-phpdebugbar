<?php

include 'bootstrap.php';

use atk4\ui\App;
use ATK4PHPDebugBar\DebugBar;

class Country extends \atk4\data\Model
{
    public $table = 'country';
    public function init()
    {
        parent::init();
        $this->addField('name', ['actual' => 'nicename', 'required' => true, 'type' => 'string']);
        $this->addField('sys_name', ['actual' => 'name', 'system' => true]);
        $this->addField('iso', ['caption' => 'ISO', 'required' => true, 'type' => 'string']);
        $this->addField('iso3', ['caption' => 'ISO3', 'required' => true, 'type' => 'string']);
        $this->addField('numcode', ['caption' => 'ISO Numeric Code', 'type' => 'number', 'required' => true]);
        $this->addField('phonecode', ['caption' => 'Phone Prefix', 'type' => 'number', 'required' => true]);
        $this->addHook('beforeSave', function ($m) {
            if (!$m['sys_name']) {
                $m['sys_name'] = strtoupper($m['name']);
            }
        });
    }

    public function validate($intent = null)
    {
        $errors = parent::validate($intent);
        if (strlen($this['iso']) !== 2) {
            $errors['iso'] = 'Must be exactly 2 characters';
        }
        if (strlen($this['iso3']) !== 3) {
            $errors['iso3'] = 'Must be exactly 3 characters';
        }
        // look if name is unique
        $c = clone $this;
        $c->unload();
        $c->tryLoadBy('name', $this['name']);
        if ($c->loaded() && $c->id != $this->id) {
            $errors['name'] = 'Country name must be unique';
        }
        return $errors;
    }
}

// start DbConnection
$db = new \atk4\data\Persistence\SQL('mysql:dbname=atk4;host=localhost', 'root', 'root');

$model_country = new Country($db);

// Migration : if not exists table create it
(\atk4\schema\Migration::getMigration($model_country))->migrate();


$app = new App([
    'title' => 'Agile UI - DebugBar',
    'db'    => $db
]);

$app->initLayout('Centered');
$app->add($debugBar = new ATK4PHPDebugBar\DebugBar());
$debugBar->setAssetsResourcesUrl('../');
//$debugBar->addDefaultCollectors();
$debugBar->addATK4PersistenceSQLCollector();

$loader = $app->add('Loader');

$loader->set(function ($l) use ($model_country) {
    $model_country->tryLoadAny();

    $country_name = $model_country->get('name');
    $l->app->getDebugBarCollector('messages')->addMessage('new message :'.$country_name);

    $l->add(['Text', 'country name :'.$country_name]);
});

/** @var Button $button */
$button = $app->add(['Button', 'test']);
$button->on('click', function ($j) use ($loader) {
    return $loader->jsReload();
});

$app->run();
