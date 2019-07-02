<?php declare(strict_types=1);

namespace Abbadon1334\ATK4PHPDebugBar;

use atk4\core\DIContainerTrait;
use atk4\core\FactoryTrait;
use atk4\ui\Exception;
use Abbadon1334\ATK4PHPDebugBar\Collector\ATK4Logger;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar as PHPDebugBar;
use DebugBar\JavascriptRenderer;
use PDO;

class DebugBar
{
    use \atk4\core\AppScopeTrait;
    use DIContainerTrait;
    use FactoryTrait;
    use \atk4\core\InitializerTrait {
        init as _init;
    }

    /** @var PHPDebugBar */
    private $debugBar;

    /** @var JavascriptRenderer */
    private $debugBarRenderer;

    /** @var PDOCollector */
    private $PDOCollector;

    protected $assets_url_relative;
    protected $assets_url_base = 'vendor/maximebf/debugbar/src/DebugBar/Resources/';

    public function init(): void
    {
        $this->_init();

        $this->debugBar         = new PHPDebugBar();
        $this->debugBarRenderer = $this->debugBar->getJavascriptRenderer();

        $this->addCollector(new MessagesCollector());

        $this->setUpApp();
    }

    public function setAssetsBaseUrl($url)
    {
        $this->assets_url_relative = $url;
    }

    public function setAssetsUrlBase($path)
    {
        $this->assets_url_base = $path;
    }

    protected function processAssets()
    {
        $relative_url = implode('/', [$this->assets_url_relative,$this->assets_url_base]);

        // already loaded by ATK
        $this->debugBarRenderer->disableVendor('jquery');

        // get debug bar enabled Assets
        list($required_css, $required_js) = $this->debugBarRenderer->getAssets(null,'');

        foreach($required_css as $css)
        {
            $this->app->requireCSS($relative_url.$css);
        }

        foreach($required_js as $js)
        {
            $this->app->requireJS($relative_url.$js);
        }
    }

    public function setUpApp(): void
    {
        $this->app->addHook('beforeRender', function ($j): void {
            $this->processAssets();
            //$this->app->html->template->appendHTML('HEAD', $this->debugBarRenderer->renderHead());
            $this->app->html->template->appendHTML('Content', $this->debugBarRenderer->render());
        });

        $this->app->addHook('beforeExit', function ($j): void {
            if(!headers_sent())
                $this->debugBar->sendDataInHeaders();
        });

        $this->app->addMethod('getDebugBar', function () {
            return $this->debugBar;
        });
    }

    public function addDefaultCollectors(): void
    {
        $this->addCollectorPhpInfo();
        $this->addCollectorRequestData();
        $this->addCollectorTimeData();
        $this->addCollectorMemory();
        $this->addCollectorExceptions();
    }

    public function addCollector(DataCollectorInterface $dataCollector): void
    {
        $this->debugBar->addCollector($dataCollector);
    }

    public function addCollectorAppLogger(): void
    {
        $this->addCollector(new ATK4Logger($this->app));
    }

    public function addPersistenceCollector(?PDO $pdo= null, string $prefix = 'db'): void
    {
        $pdo = $pdo ?? $this->app->db->connection->connection() ?? null;

        if (!is_a($pdo, PDO::class)) {
            throw new Exception([
                'This collector needs a PDO instance as argument or defined in the $app',
            ]);
        }

        $pdoRead  = new TraceablePDO($pdo);
        $pdoWrite = new TraceablePDO($pdo);

        $pdoCollector = new PDOCollector();

        $pdoCollector->addConnection($pdoRead, $prefix.'-read');
        $pdoCollector->addConnection($pdoWrite, $prefix.'-write');

        $this->addCollector($pdoCollector);
    }

    public function addCollectorPhpInfo(): void
    {
        $this->addCollector(new PhpInfoCollector());
    }

    public function addCollectorRequestData(): void
    {
        $this->addCollector(new RequestDataCollector());
    }

    public function addCollectorTimeData(): void
    {
        $this->addCollector(new TimeDataCollector());
    }

    public function addCollectorMemory(): void
    {
        $this->addCollector(new MemoryCollector());
    }

    public function addCollectorExceptions(): void
    {
        $this->addCollector(new ExceptionsCollector());
    }

    public function getCollector(string $name): DataCollectorInterface
    {
        return $this->debugBar->getCollector($name);
    }
}
