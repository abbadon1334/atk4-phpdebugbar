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
use DebugBar\OpenHandler;
use DebugBar\Storage\FileStorage;

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
    protected $assets_resources_path = 'vendor'.DIRECTORY_SEPARATOR.'maximebf'.DIRECTORY_SEPARATOR.'debugbar'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'DebugBar'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR;

    /** @var TimeDataCollector */
    protected $timeDataCollector;

    /** @var string */
    public $open_handler;

    /** @var FileStorage */
    public $file_storage;

    public function __construct($defaults = [])
    {
        $this->setDefaults($defaults);
    }

    /**
     * @throws DebugBarException
     * @throws \atk4\core\Exception
     */
    public function init(): void
    {
        $this->_init();

        $this->open_handler = $this->open_handler ?? '/debugbar_handler';

        $this->file_storage = $this->file_storage ?? new FileStorage(getcwd().'/tmp');

        $this->timeDataCollector = new TimeDataCollector();

        $this->debugBar = new \DebugBar\DebugBar();

        $this->debugBarRenderer = $this->debugBar->getJavascriptRenderer();

        $this->addCollector(new MessagesCollector());

        $this->setUpApp();

        if ($this->app->hasMethod('getRouter')) {
            // @codeCoverageIgnoreStart
            $router = $this->app->getRouter();

            $router
                ->addRoute($this->open_handler)
                ->addMethod('GET')
                ->setHandler(
                    new \Abbadon1334\ATKFastRoute\Handler\RoutedCallable(function (...$parameters): void {
                        $openHandler = new OpenHandler($this->debugBar);
                        $openHandler->handle();
                    })
                );

            $router
                ->addRoute('/debugbar/{path:.+}')
                ->addMethod('GET')
                ->setHandler(
                    new \Abbadon1334\ATKFastRoute\Handler\RoutedServeStatic(
                        __DIR__.'/../../../maximebf/debugbar/src/DebugBar/Resources',
                        [
                            'css',
                            'js',
                            'woff',
                            'woff2',
                            'ttf',
                        ]
                    )
                );
            // @codeCoverageIgnoreEnd
        }
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
        $this->debugBar->setStorage($this->file_storage);
        $this->debugBarRenderer->setOpenHandlerUrl($this->open_handler);
        $this->debugBar->sendDataInHeaders($this->isJsonRequest() ? true : null);

        $this->app->addHook('beforeRender', function ($j): void {
            $this->processAssets();
        });

        $this->app->addHook('beforeOutput', function ($j): void {
            $this->debugBar->collect();
            $this->app->html->template->appendHTML(
                'Content',
                $this->debugBarRenderer->render(true, ! $this->isJsonRequest())
            );
        });

        $this->app->addHook('beforeExit', function ($j): void {
            $this->debugBar->collect();
        }, [], 200);

        $this->app->addMethod(
            'getDebugBar',
            function ($app) {
                return $this->getDebugBar();
            }
        );

        $this->app->addMethod(
            'getDebugBarCollector',
            function ($app, string $name) {
                return $this->getCollector($name);
            }
        );

        $this->app->addMethod(
            'hasDebugBarCollector',
            function ($app, string $name) {
                return $this->hasCollector($name);
            }
        );
    }

    /**
     * Most of the ajax request will require sending exception in json
     * instead of html, except for tab.
     *
     * @return bool
     */
    protected function isJsonRequest()
    {
        //No need of it
        //if (isset($_GET['__atk_tab'])) {
        //    return false;
        //}
        //

        if (isset($_GET['json'])) {
            return true;
        }

        return 'xmlhttprequest' === strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    }

    protected function processAssets(): void
    {
        $relative_url = $this->assets_resources_url;
        $relative_url .= $relative_url === null ? '/' : '';
        $relative_url .= $this->assets_resources_path;

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

        if (! $this->hasCollector($this->timeDataCollector->getName())) {
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
        $db = $persistence ?? $this->app->db;

        $pdo = new TraceablePDO($db->connection->connection());
        $this->addCollector(new PDOCollector($pdo, $this->timeDataCollector));

        if (! $this->hasCollector($this->timeDataCollector->getName())) {
            $this->addCollector($this->timeDataCollector);
        }
    }
}
