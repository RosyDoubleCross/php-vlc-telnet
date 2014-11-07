<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../autoload.php')) {
    require_once __DIR__ . '/../../autoload.php';
} else {
    die("Can't find composer autoloader\n");
}

use RXX\VLCTelnet\VLCTelnet;
use Zend\Config\Config;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

$videos = array('clip1.mp4', 'clip2.mp4', 'clip3.mp4');

// specify alternate config
$config = new Config(array('host' => 'localhost', 'port' => 13013));

// create a null logger
$nullLogger = new Logger('null', array(new NullHandler()));

// connect to VLC telnet console at localhost:13013
$vlc = new VLCTelnet($config, $nullLogger, $nullLogger);

// clear the playlist
$vlc->clear();

// add/play a video from our array at random
$vlc->add($videos[array_rand($videos)]);

// repeat the single video over and over
$vlc->repeat(true);

// when script ends we will disconnect, but the video will play forever

