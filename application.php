#!/usr/bin/env php
<?php

require_once __DIR__.'/bootstrap.php';

use Symfony\Component\Console\Application;

use TheFox\Console\Command\ServerCommand;

$application = new Application('SMTPd', '0.1.x-dev');
$application->add(new ServerCommand());
$application->run();
