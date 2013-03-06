<?php
require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$loop->addReadStream(STDIN, function($stream) use($loop) {
        $data = fgets($stream);
        fwrite(STDOUT, $data);
        fwrite(STDERR, $data);
    });

$loop->addPeriodicTimer(1, function() {
        pcntl_signal_dispatch();
    });


pcntl_signal(SIGTERM, function() use($loop) {
        $loop->stop();
        exit(0);
    });
$loop->run();
