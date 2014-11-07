<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../autoload.php')) {
    require_once __DIR__ . '/../../autoload.php';
} else {
    die("Can't find composer autoloader\n");
}

use RXX\VLCTelnet\VLCTelnet;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;
use Monolog\Formatter\LineFormatter;

$actionLog = new Logger('VLCTelnet.action', array(new NullHandler()));

$stream = fopen('socket.log', 'w');
$handler = new StreamHandler($stream);
$formatter = new LineFormatter('%message%', null, true);
$handler->setFormatter($formatter);
$socketLog = new Logger('VLCTelnet.socket', array($handler));

// connect to VLC telnet console at localhost:5023
$vlc = new VLCTelnet(null, $actionLog, $socketLog);

if (!$vlc->isPlaying()) {
    $msg = 'Media is not playing';
} else {
    $msg = 'Media at position ' . $vlc->getTime() . '/' . $vlc->getLength();
}

echo "{$msg}\n";

