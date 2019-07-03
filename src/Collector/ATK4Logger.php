<?php

declare(strict_types=1);

namespace ATK4PHPDebugBar\Collector;

use atk4\ui\App;
use DebugBar\DataCollector\MessagesCollector;

class ATK4Logger extends MessagesCollector
{
    protected $app;

    /** @var \Psr\Log\LoggerInterface */
    protected $app_original_logger;

    public function __construct(App $app)
    {
        $this->app_original_logger = $app->logger ?? null;
        $app->logger = $this;
    }

    public function write($message, $level): void
    {
        if ($this->app_original_logger) {
            $this->app_original_logger->log($message, $level);
        }

        $this->addMessage($message, $level);
    }

    public function getName()
    {
        return 'atk4-logger';
    }

    public function log($level, $message, array $context = []): void
    {
        $this->write($message, $level);
    }
}
