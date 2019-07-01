<?php

namespace ATK4PHPDebugBar;

use atk4\ui\Exception;
use ATK4PHPDebugBar\Collector\ATK4Logger;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar as PHPDebugBar;
use PDO;

class DebugBar
{
    use \atk4\core\AppScopeTrait;
    use \atk4\core\InitializerTrait {
        init as _init;
    }

    /** @var PHPDebugBar */
    private $debugbar;

    private $PDOCollector;
    /**
     * @var bool
     */
    protected $_jquery_no_conflict;

    public function __construct()
    {
        $this->debugbar = new PHPDebugBar();
    }

    public function init()
    {
        $this->_init();
    }

    public function _setJQueryNoConflict(bool $enabled)
    {
        $this->_jquery_no_conflict = $enabled;
    }

    public function setUpApp()
    {
        $this->app->addHook('beforeRender', function ($j) {

            $debugbar_renderer = $this->debugbar->getJavascriptRenderer('/php-debugbar');

            if($this->_jquery_no_conflict) {
                $debugbar_renderer->setEnableJqueryNoConflict();
            }

            $this->app->html->template->appendHTML('HEAD', $debugbar_renderer->renderHead());
            $this->app->html->template->appendHTML('Content', $debugbar_renderer->render());
        });

        $this->app->addHook('beforeExit', function ($j) {
            $this->debugbar->sendDataInHeaders();
        });

        $this->app->addMethod('getDebugBar', function () : PHPDebugBar {
            return $this->debugbar;
        });
    }

    public function addDefaultCollectors()
    {
        $this->debugbar->addCollector(new PhpInfoCollector());
        $this->debugbar->addCollector(new MessagesCollector());
        $this->debugbar->addCollector(new RequestDataCollector());
        $this->debugbar->addCollector(new TimeDataCollector());
        $this->debugbar->addCollector(new MemoryCollector());
        $this->debugbar->addCollector(new ExceptionsCollector());
    }

    public function addCollectorAppLogger()
    {
        $this->debugbar->addCollector(new ATK4Logger($this->app));
    }

    public function addPersistenceCollector(?PDO $pdo= null, string $prefix = 'db') : void
    {
        $pdo = $pdo ?? $this->app->db->connection->connection() ?? null;

        if (!is_a($pdo,PDO::class))
        {
            throw new Exception([
                'This collector needs a PDO instance as argument or defined in the $app'
            ]);
        }

        $pdoRead  = new TraceablePDO($pdo);
        $pdoWrite = new TraceablePDO($pdo);

        $pdoCollector = new PDOCollector();

        $pdoCollector->addConnection($pdoRead, $prefix.'-read');
        $pdoCollector->addConnection($pdoWrite, $prefix.'-write');

        $this->debugbar->addCollector($pdoCollector);
    }
}