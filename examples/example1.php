<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../autoload.php')) {
    require_once __DIR__ . '/../../autoload.php';
} else {
    die("Can't find composer autoloader\n");
}

use RXX\VLCTelnet\VLCTelnet;

$videos = array('clip1.mp4', 'clip2.mp4', 'clip3.mp4');

// connect to VLC telnet console at localhost:5023
$vlc = new VLCTelnet();

// clear the playlist
$vlc->clear();

// do not repeat a single playlist item
$vlc->repeat(false);

// loop (repeat) the entire playlist
$vlc->loop(true);

// play videos from playlist in random order
$vlc->random(true);

// add the videos to the queue and play immediately
foreach ($videos as $video) {
    $vlc->add($video);
}

// when script ends we will disconnect, but videos will run forever

