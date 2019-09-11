<?php

declare(strict_types=1);

namespace ATK4PHPDebugBar;

use atk4\core\AppScopeTrait;
use atk4\core\DIContainerTrait;
use atk4\core\FactoryTrait;
use atk4\core\InitializerTrait;
use atk4\data\Persistence;
use atk4\ui\Exception;
use ATK4PHPDebugBar\Bridge\ATK4Collector;
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

    /**
     * @var string
     */
    protected $assets_resources_url;
    /**
     * @var string
     */
    protected $assets_resources_path = 'vendor' . DIRECTORY_SEPARATOR . 'maximebf' . DIRECTORY_SEPARATOR . 'debugbar' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'DebugBar' . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR;

    /** @var TimeDataCollector */
    protected $timeDataCollector;

    /**
     * @throws DebugBarException
     * @throws \atk4\core\Exception
     */
    public function init(): void
    {
        $this->_init();

        $this->timeDataCollector = new TimeDataCollector();

        $this->debugBar         = new \DebugBar\DebugBar();
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
        $this->app->addHook(
            'beforeRender', function ($j): void {
            $this->processAssets();
        }
        );

        $this->app->addHook(
            'beforeOutput', function ($j): void {
            $this->app->html->template->appendHTML('Content', $this->debugBarRenderer->render());
        }
        );

        $this->app->addHook(
            'beforeExit', function ($j): void {
            if (!headers_sent()) {
                $this->debugBar->sendDataInHeaders(false);
            }
        }
        );

        $this->app->addMethod(
            'getDebugBar', function ($app) {
            return $this->getDebugBar();
        }
        );

        $this->app->addMethod(
            'getDebugBarCollector', function ($app, string $name) {
            return $this->getCollector($name);
        }
        );

        $this->app->addMethod(
            'hasDebugBarCollector', function ($app, string $name) {
            return $this->hasCollector($name);
        }
        );
    }

    protected function processAssets()
    {
        $relative_url = implode('/', [$this->assets_resources_url, $this->assets_resources_path]);

        // already loaded by ATK
        $this->debugBarRenderer->disableVendor('jquery');

        // get debug bar enabled Assets
        [$required_css, $required_js] = $this->debugBarRenderer->getAssets(null, '');

        foreach ($required_css as $css) {
            $this->app->requireCSS($relative_url . $css);
        }

        foreach ($required_js as $js) {
            $this->app->requireJS($relative_url . $js);
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
     * @param string $name
     *
     * @throws DebugBarException
     * @return DataCollectorInterface
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
        $this->addCollector(new MemoryCollector());
        $this->addCollector(new ExceptionsCollector());

        if (!$this->hasCollector($this->timeDataCollector->getName())) {
            $this->addCollector($this->timeDataCollector);
        }
    }

    /**
     * @throws DebugBarException
     */
    public function addATK4LoggerCollector(): void
    {
        $this->addCollector(new ATK4Logger($this->app));
    }

    /**
     * @param Persistence\SQL|null $persistence
     *
     * @throws DebugBarException
     */
    public function addATK4PersistenceSQLCollector(?Persistence\SQL $persistence = null): void
    {
        $persistence = $persistence ?? $this->app->db;

        $pdo = new TraceablePDO($persistence->connection->connection());
        $this->addCollector(new PDOCollector($pdo, $this->timeDataCollector));

        if (!$this->hasCollector($this->timeDataCollector->getName())) {
            $this->addCollector($this->timeDataCollector);
        }
    }
}
