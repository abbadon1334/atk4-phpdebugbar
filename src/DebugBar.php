<?php

declare(strict_types=1);

namespace ATK4PHPDebugBar;

use atk4\core\AppScopeTrait;
use atk4\core\DIContainerTrait;
use atk4\core\FactoryTrait;
use atk4\core\InitializerTrait;
use atk4\data\Persistence;
use atk4\ui\Exception;
use ATK4PHPDebugBar\Collector\ATK4Logger;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBarException;
use DebugBar\JavascriptRenderer;
use PDO;

class DebugBar
{
    use AppScopeTrait;
    use DIContainerTrait;
    use FactoryTrait;
    use InitializerTrait {
        init as _init;
    }

    /** @var \DebugBar\DebugBar */
    protected $debugBar;

    /** @var JavascriptRenderer */
    protected $debugBarRenderer;

    /** @var PDOCollector */
    protected $PDOCollector;

    /**
     * @var string
     */
    protected $assets_resources_url;
    /**
     * @var string
     */
    protected $assets_resources_path = 'vendor'.DIRECTORY_SEPARATOR.'maximebf'.DIRECTORY_SEPARATOR.'debugbar'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'DebugBar'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR;


    /**
     * @throws DebugBarException
     * @throws \atk4\core\Exception
     */
    public function init(): void
    {
        $this->_init();

        $this->debugBar = new \DebugBar\DebugBar();
        $this->debugBarRenderer = $this->debugBar->getJavascriptRenderer();

        $this->addCollector(new MessagesCollector());

        $this->setUpApp();
    }

    /**
     * @param DataCollectorInterface $dataCollector
     *
     * @throws DebugBarException
     */
    public function addCollector(DataCollectorInterface $dataCollector): void
    {
        $this->debugBar->addCollector($dataCollector);
    }

    /**
     * @throws \atk4\core\Exception
     */
    protected function setUpApp(): void
    {
        $this->app->addHook('beforeRender', function ($j): void {
            $this->processAssets();
            //$this->app->html->template->appendHTML('HEAD', $this->debugBarRenderer->renderHead());
            $this->app->html->template->appendHTML('Content', $this->debugBarRenderer->render());
        });

        $this->app->addHook('beforeExit', function ($j): void {
            if (! headers_sent()) {
                $this->debugBar->sendDataInHeaders();
            }
        });

        $this->app->addMethod('getDebugBar', function ($app) {
            return $this->getDebugBar();
        });

        $this->app->addMethod('getDebugBarCollector', function ($app, string $name) {
            return $this->getCollector($name);
        });

        $this->app->addMethod('hasDebugBarCollector', function ($app, string $name) {
            return $this->hasCollector($name);
        });
    }

    protected function processAssets()
    {
        $relative_url = implode('/', [$this->assets_resources_url, $this->assets_resources_path]);

        // already loaded by ATK
        $this->debugBarRenderer->disableVendor('jquery');

        // get debug bar enabled Assets
        [$required_css, $required_js] = $this->debugBarRenderer->getAssets(null, '');

        foreach ($required_css as $css) {
            $this->app->requireCSS($relative_url.$css);
        }

        foreach ($required_js as $js) {
            $this->app->requireJS($relative_url.$js);
        }
    }

    /**
     * @return \DebugBar\DebugBar
     */
    public function getDebugBar(): \DebugBar\DebugBar
    {
        return $this->debugBar;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setAssetsResourcesUrl(string $url)
    {
        $this->assets_resources_url = $url;

        return $this;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setAssetsResourcesPath(string $path)
    {
        $this->assets_resources_path = $path;

        return $this;
    }

    /**
     * @throws DebugBarException
     */
    public function addDefaultCollectors(): void
    {
        $this->addCollector(new PhpInfoCollector());
        $this->addCollector(new RequestDataCollector());
        $this->addCollector(new TimeDataCollector());
        $this->addCollector(new MemoryCollector());
        $this->addCollector(new ExceptionsCollector());
    }

    /**
     * @param string $name
     *
     * @return DataCollectorInterface
     * @throws DebugBarException
     */
    public function getCollector(string $name): DataCollectorInterface
    {
        return $this->debugBar->getCollector($name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasCollector(string $name): bool
    {
        return $this->debugBar->hasCollector($name);
    }

    /**
     * @throws DebugBarException
     */
    public function addATK4LoggerCollector(): void
    {
        $this->addCollector(new ATK4Logger($this->app));
    }

    /**
     * @param string|null          $prefix
     * @param Persistence\SQL|null $persistence
     *
     * @throws Exception
     * @throws DebugBarException
     */
    public function addATK4PersistenceSQLCollector(?Persistence\SQL $persistence = null, string $prefix = null): void
    {
        $persistence = $persistence ?? $this->app->db ?? null;
        $prefix = $prefix ?? 'db';

        $this->addCollectorPDO($persistence->connection->connection(), $prefix);
    }

    /**
     * @param string|null $prefix
     * @param PDO|null    $pdo
     *
     * @throws DebugBarException
     */
    public function addCollectorPDO(PDO $pdo = null, string $prefix = null): void
    {
        $prefix = $prefix ?? 'db';

        $pdoRead = new TraceablePDO($pdo);
        $pdoWrite = new TraceablePDO($pdo);

        $pdoCollector = new PDOCollector();

        $pdoCollector->addConnection($pdoRead, $prefix.'-read');
        $pdoCollector->addConnection($pdoWrite, $prefix.'-write');

        $this->addCollector($pdoCollector);
    }
}
