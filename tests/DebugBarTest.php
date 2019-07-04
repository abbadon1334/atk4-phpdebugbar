<?php

namespace ATK4PHPDebugBar;


use atk4\ui\App;
use DebugBar\DataCollector\PhpInfoCollector;
use PHPUnit\Framework\TestCase;

class DebugBarTest extends TestCase
{
    protected $app;

    public function setUp() : void
    {
        $app = new App([
            'title' => 'Agile UI - DebugBar',
        ]);

        $app->initLayout('Centered');
        $app->add($debugBar = new \ATK4PHPDebugBar\DebugBar());
        $debugBar->setAssetsResourcesUrl('../');
        $debugBar->addDefaultCollectors();

        $this->app = $app;
    }

    public function testGetDebugBar_AppDynamicMethods()
    {
        $this->assertEquals(\DebugBar\DebugBar::class, get_class($this->app->getDebugBar()));

        $this->assertEquals(PhpInfoCollector::class, get_class($this->app->getDebugBarCollector('php')));

        $this->assertEquals(true, $this->app->hasDebugBarCollector('php'));
        $this->assertEquals(false, $this->app->hasDebugBarCollector('not exists'));
    }
}
