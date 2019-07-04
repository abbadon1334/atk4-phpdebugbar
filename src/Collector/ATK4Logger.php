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
        if ($app->logger instanceof \Psr\Log\LoggerInterface) {
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

    public function getName()
    {
        return 'ATKAppLog';
    }
}
