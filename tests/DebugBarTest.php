<?php

namespace ATK4PHPDebugBar\Test;

use atk4\ui\App;
use ATK4PHPDebugBar\DebugBar;
use DebugBar\DataCollector\PhpInfoCollector;
use PHPUnit\Framework\TestCase;

class DebugBarTest extends TestCase
{
    protected $app;
    protected $debugbar;

    public function setUp(): void
    {
        $app = new App([
            'title' => 'Agile UI - DebugBar',
        ]);

        $app->initLayout('Centered');
        $app->add($this->debugBar = new DebugBar());
        $this->debugBar->setAssetsResourcesUrl('../');

        /* just for coverage call setAssetsResourcesPath*/
        $this->debugBar->setAssetsResourcesPath('vendor/maximebf/debugbar/src/DebugBar/Resources/');

        $this->debugBar->addDefaultCollectors();

        $this->app = $app;
    }

    public function testGetDebugBar_AppDynamicMethods()
    {
        $this->assertEquals(\DebugBar\DebugBar::class, get_class($this->app->getDebugBar()));

        $this->assertEquals(PhpInfoCollector::class, get_class($this->app->getDebugBarCollector('php')));

        $this->assertEquals(true, $this->app->hasDebugBarCollector('php'));
        $this->assertEquals(false, $this->app->hasDebugBarCollector('not exists'));
    }

    /**
     * Only for increase coverage, cover 1 line of code.
     *
     * @runInSeparateProcess
     */
    public function testSendMessagesViaHeaders()
    {
        $this->app->hook('beforeExit');
        $this->addToAssertionCount(1);
    }
}
