<?php


namespace ATK4PHPDebugBar\Collector;

use atk4\core\DebugTrait;
use atk4\ui\App;
use Psr\Log\LogLevel;
use DebugBar\DataCollector\MessagesCollector;

class ATK4Logger extends MessagesCollector
{
    protected $app;

    protected $app_original_logger;

    public function __construct(App $app)
    {
        $this->app_original_logger = $app->logger;
        $app->logger = $this;
    }

    public function write($message, $level)
    {
        if ($this->app_original_logger) {
            $this->app_original_logger->log($message, $level);
        }

        $this->addMessage($message,$level);
    }

    public function getName()
    {
        return 'atk4';
    }

    public function log($level, $message, array $context = array())
    {
        $this->write($message, $level);
    }
}