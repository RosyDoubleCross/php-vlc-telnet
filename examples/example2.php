<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../autoload.php')) {
    require_once __DIR__ . '/../../autoload.php';
} else {
    die("Can't find composer autoloader\n");
}

use RXX\VLCTelnet\VLCTelnet;

// connect to VLC telnet console at localhost:5023
$vlc = new VLCTelnet();

if (!$vlc->isPlaying()) {
    $msg = 'Media is not playing';
} else {
    $msg = 'Media at position ' . $vlc->getTime() . '/' . $vlc->getLength();
}

unset($vlc);

echo "{$msg}\n";

