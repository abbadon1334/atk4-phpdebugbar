<?php

namespace ATK4PHPDebugBar\Test;

use atk4\core\Exception;
use PHPUnit\Framework\TestCase;

class DemosTest extends TestCase
{
    public function tearDown(): void
    {
        if (file_exists(__DIR__.'/../demos/test.log') {
            unlink(__DIR__.'/../demos/test.log');
        }
    }

    /**
     * @runInSeparateProcess
     * @dataProvider dataProviderTestDemos
     */
    public function testDemos($file)
    {
        try {
            ob_start();

            include __DIR__.'/../demos/'.$file;

            $content = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_flush();

            $e = new Exception($e->getMessage(), $e->getCode(), $e);

            $e->addMoreInfo('path', $file);

            throw $e;
        }

        $this->addToAssertionCount(1);
    }

    public function dataProviderTestDemos()
    {
        return [
            ['example.php'],
            ['example-logger.php'],
            ['example-logger-debugTrait.php'],
            ['example-persistence.php'],
        ];
    }
}
