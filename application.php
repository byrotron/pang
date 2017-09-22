#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use \Pang\Command\InstallCommand;

$application = new Application();

// ... register commands
$application->add(new InstallCommand());
$application->run();