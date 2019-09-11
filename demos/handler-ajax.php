<?php

require_once 'bootstrap.php';

use atk4\ui\App;
use DebugBar\DebugBar;
use DebugBar\OpenHandler;
use DebugBar\Storage\FileStorage;

$debugbar = new DebugBar();
$debugbar->setStorage( new FileStorage('./tmp'));

$openHandler = new OpenHandler($debugbar);
$openHandler->handle();