<?php

namespace RXX\VLCTelnet;

use Zend\Config\Config;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use RXX\Colors\Colors;
use RuntimeException;

class VLCTelnet
{
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 5023;

    const SOCKET_TIMEOUT_S = 0;
    const SOCKET_TIMEOUT_US = 100000;

    const MAX_SEND_TRIES = 3;
    const MAX_RECEIVE_TRIES = 30;

    protected $socket = null;
    protected $actionLog = null;
    protected $socketLog = null;

    public function __construct(Config $config = null, Logger $actionLog = null, Logger $socketLog = null)
    {
        if ($config === null) {
            $config = new Config(array());
        }

        $this->initializeActionLog($actionLog);
        $this->initializeSocketLog($socketLog);

        $this->initializeSocket(
            $config->get('host', self::DEFAULT_HOST),
            $config->get('port', self::DEFAULT_PORT)
        );

        // let's get to the first prompt
        $this->receive();
    }

    public function __destruct()
    {
        if ($this->socket !== null) {

            $this->logAction('Finalizing socket');

            $this->command('quit');

            if ($this->socket !== null) {
                fclose($this->socket);
            }

        }
    }

    public function getTime()
    {
        $this->logAction('Getting time');

        $time = $this->request('get_time');

        if (preg_match('#^Error in `get_time\'.* \(vlc_object expected, got nil\)$#', $time)) {
            $time = 0;
        } elseif (!is_numeric($time)) {
            $this->unexpected($time, 'get_time');
        }

        return (int)$time;
    }

    public function getLength()
    {
        $this->logAction('Getting length');

        $length = $this->request('get_length');

        if (preg_match('#^Error in `get_length\'.* \(vlc_object expected, got nil\)$#', $length)) {
            $length = 0;
        } elseif (!is_numeric($length)) {
            $this->unexpected($length, 'get_length');
        }

        return (int)$length;
    }

    public function getTitle()
    {
        $this->logAction('Getting title');

        $title = $this->request('get_title');

        return $title;
    }

    public function isPlaying()
    {
        $this->logAction('Checking if stream is playing');

        $isPlaying = $this->request('is_playing');

        if ($isPlaying === '1') {
            return true;
        }

        if ($isPlaying === '0') {
            return false;
        }

        $this->unexpected($isPlaying, 'is_playing');
    }
        
    public function getPlaylist()
    {
        $this->logAction('Show items currently in playlist');

        $playlist = $this->request('playlist');

        return $playlist;
    }

    
    
    public function seek($position)
    {
        $this->logAction("Seeking to {$position}");
        return $this->command("seek {$position}");
    }

    public function clear()
    {
        $this->logAction('Clearing playlist');
        return $this->command('clear');
    }

    public function add($video)
    {
        $this->logAction("Adding '{$video}' to playlist");
        return $this->command("add {$video}");
    }

    public function enqueue($video)
    {
        $this->logAction("Enqueueing '{$video}' to playlist");
        return $this->command("enqueue {$video}");
    }

    public function play()
    {
        $this->logAction('Resuming stream');
        return $this->command('play');
    }

    public function stop()
    {
        $this->logAction('Stopping stream');
        return $this->command('stop');
    }

    public function pause()
    {
        $this->logAction('Pausing stream');
        return $this->command('pause');
    }

    public function next()
    {
        $this->logAction('Skipping to next video');
        return $this->command('next');
    }

    public function prev()
    {
        $this->logAction('Skipping to previous video');
        return $this->command('prev');
    }

    public function shutdown()
    {
        $this->logAction('Shutting down stream');
        return $this->command('shutdown');
    }

    public function gotoItem($item)
    {
        $this->logAction("Going to playlist item {$item}");
        return $this->command("goto {$item}");
    }

    public function setLoop($loop)
    {
        $loop = $loop ? 'on' : 'off';
        $this->logAction("Setting loop to {$loop}");
        return $this->command("loop {$loop}");
    }

    public function setRandom($random)
    {
        $random = $random ? 'on' : 'off';
        $this->logAction("Setting random to {$random}");
        return $this->command("random {$random}");
    }

    public function setRepeat($repeat)
    {
        $repeat = $repeat ? 'on' : 'off';
        $this->logAction("Setting repeat to {$repeat}");
        return $this->command("repeat {$repeat}");
    }

    protected function initializeActionLog(Logger $actionLog = null)
    {
        if ($actionLog === null) {
            $handler = new StreamHandler(STDOUT);
            $formatter = new LineFormatter("%datetime% %message%\n");
            $handler->setFormatter($formatter);
            $this->actionLog = new Logger('VLCTelnet.action', array($handler));
        } else {
            $this->actionLog = $actionLog;
        }

        $this->logAction('** Starting action log **');
    }

    protected function initializeSocketLog(Logger $socketLog = null)
    {
        if ($socketLog === null) {
            $this->logAction('Creating socket log');
            $handler = new StreamHandler(STDERR);
            $formatter = new LineFormatter('%message%', null, true);
            $handler->setFormatter($formatter);
            $this->socketLog = new Logger('VLCTelnet.socket', array($handler));
        } else {
            $this->logAction('Using existing socket log');
            $this->socketLog = $socketLog;
        }
    }

    protected function initializeSocket($host, $port)
    {
        $this->logAction('Opening socket connection to VLC');

        $this->socket = fsockopen($host, $port, $errno, $error);

        if ($this->socket === false) {
            throw new RuntimeException("Failed to open socket: {$error} ({$errno})");
        }

        if (!stream_set_timeout($this->socket, self::SOCKET_TIMEOUT_S, self::SOCKET_TIMEOUT_US)) {
            throw new RuntimeException('Failed to set socket timeout');
        }

        $this->logSocket("\n** New connection **\n\n");
        $this->logAction('Opened socket connection to VLC');
    }

    protected function request($request)
    {
        $this->send("{$request}\n");
        return trim($this->receive());
    }

    protected function command($command)
    {
        $response = $this->request($command);

        if ($response) {
            $args = explode(' ', $command);
            $this->unexpected($response, $args[0]);
        }

        return true;
    }

    protected function unexpected($response, $command)
    {
        throw new RuntimeException("Received unexpected response from {$command}: '{$response}'");
    }

    protected function send($data)
    {
        $tries = 0;

        while ($data && ++$tries <= self::MAX_SEND_TRIES) {

            $written = fwrite($this->socket, $data);

            if ($written === false) {
                throw new RuntimeException('Failed to write to socket');
            }

            if ($written) {
                $tries = 0;
                $this->logSend(substr($data, 0, $written));
                $data = substr($data, $written);
            }

        }

        if ($data) {
            throw new RuntimeException('Failed to send all data to socket');
        }

        return true;
    }

    protected function receive()
    {
        $data = '';
        $tries = 0;

        while (++$tries <= self::MAX_RECEIVE_TRIES) {

            $buf = fread($this->socket, 1024);

            if ($buf === false) {
                throw new RuntimeException('Failed to read from socket');
            }

            if ($buf) {

                $tries = 0;
                $this->logReceive($buf);
                $data .= $buf;

            } elseif ($tries === 1) {
            
                if (preg_match('#(^|\\n)Bye-bye!\\r\\n(> )?$#', $data)) {
                    fclose($this->socket);
                    $this->socket = null;
                    return '';
                }

                if (preg_match('#(^|\\n)> $#', $data)) {
                    return substr($data, 0, -2);
                }

            }

        }

        throw new RuntimeException('Failed to receive prompt');
    }

    protected function logSocket($data, $color = Colors::MAGENTA)
    {
        $this->socketLog->addRecord(Logger::DEBUG, Colors::cstr($data, $color, true));
    }

    protected function logSend($data)
    {
        $this->logSocket($data, Colors::CYAN);
    }

    protected function logReceive($data)
    {
        $this->logSocket($data, Colors::RED);
    }

    protected function logAction($data)
    {
        $this->actionLog->addRecord(Logger::INFO, Colors::cstr($data, Colors::YELLOW, true));
    }
}

