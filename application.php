#!/usr/bin/env php
<?php

require_once __DIR__.'/bootstrap.php';

use Symfony\Component\Console\Application;

use TheFox\Console\Command\InfoCommand;
use TheFox\Console\Command\ServerCommand;
use TheFox\Smtp\Smtpd;

$application = new Application(Smtpd::NAME, Smtpd::VERSION);
$application->add(new InfoCommand());
$application->add(new ServerCommand());
$application->run();
