<?php

declare(strict_types=1);

namespace ATK4PHPDebugBar\Collector;

use atk4\ui\App;
use DebugBar\DataCollector\MessagesCollector;
use Psr\Log\LoggerInterface;

class ATK4Logger extends MessagesCollector
{
    protected $app;

    /** @var LoggerInterface */
    protected $app_original_logger;

    public function __construct(App $app, $name = 'app')
    {
        parent::__construct($name);

        if ($app->logger instanceof LoggerInterface) {
            $this->app_original_logger = clone $app->logger;
            unset($app->logger);
        }

        $app->logger = $this;
    }

    public function addMessage($message, $label = 'info', $isString = true)
    {
        parent::addMessage($message, $label, $isString);

        if ($this->app_original_logger) {
            $this->app_original_logger->log($label, $message);
        }
    }
}
