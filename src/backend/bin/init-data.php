<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SeQura\Demo\Bootstrap;
use SeQura\Demo\Config;
use SeQura\Demo\Console\InitDataCommand;

Config::load(__DIR__ . '/../.env');

Bootstrap::init();

$command = new InitDataCommand();
$command->execute();
