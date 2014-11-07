php-vlc-telnet
==============

This is a tiny PHP library for interacting with VLC telnet console to implement custom video stream automation or create alternate interfaces.

Usage
-----

The VLCTelnet class connects to a single VLC telnet console and provides methods for most of the commands that are worth using. It uses sensible defaults or can be configured more extensively by way of a Zend Config object and/or Monolog Loggers.

```php
$vlc = new \RXX\VLCTelnet\VLCTelnet();
$vlc->clear();
$vlc->repeat(false);
$vlc->loop(true);
$vlc->random(true);
foreach ($videos as $video) {
    $vlc->add($video);
}
```

By default, as in the example above, the VLCTelnet object will connect to localhost port 5023. It will output action log information to stdout, and it will output telnet traffic to stderr. Both of these outputs use ANSI colors for clarity. This is especially useful with the telnet traffic to distinguish which text is going which direction.

To connect to a different endpoint, you must provide a Zend Config object with "host" and "port" values set. You may also provide alternative Monolog Logger targets for the action log and the socket log.

```php
$config = new \Zend\Config\Config(array('host' => 'foo.bar', 'port' => 13013));
$nullLogger = new \Monolog\Logger('null', new \Monolog\Handler\NullHandler());
$vlc = new \RXX\VLCTelnet\VLCTelnet($config, $nullLogger, $nullLogger);
```

License
-------

php-vlc-telnet
Copyright (C) 2014  Joe Lafiosca <joe@lafiosca.com>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
