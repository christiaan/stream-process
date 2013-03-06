StreamProcess
============
[![Build Status](https://travis-ci.org/christiaan/stream-process.png?branch=master)](https://travis-ci.org/christiaan/stream-process)

Easily spawn processes and communicate with them using streams. Mainly build to be used together with [react/event-loop](https://packagist.org/packages/react/event-loop).

Installation
------------
````sh
composer.phar require christiaan/stream-process
````
Usage
-----
````php
$loop = \React\EventLoop\Factory::create();

$child = new StreamProcess('php someWorkerProcess.php');

$loop->addReadStream($child->getReadStream(), function($stream) {
        $data = fgets($stream);
        fwrite(STDOUT, $data);
    });

$loop->addPeriodicTimer(1, function() {
        pcntl_signal_dispatch();
    });


pcntl_signal(SIGTERM, function() use($loop) {
        $loop->stop();
        // Cleanup before closing
        exit(0);
    });


fwrite($child->getWriteStream(), 'start');
$loop->run();

````

Known issues
------------
Not working on Windows because of this [bug](https://bugs.php.net/bug.php?id=47918)

Could possible be fixed by using files instead of direct streams.