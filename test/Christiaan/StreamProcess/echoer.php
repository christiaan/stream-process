<?php
require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$loop->addReadStream(STDIN, function($stream) use($loop) {
        $data = fgets($stream);
        fwrite(STDOUT, $data);
        fwrite(STDERR, $data);
    });

$loop->addPeriodicTimer(1000000, function() {
        pcntl_signal_dispatch();
    });


$pcntl = new \MKraemer\ReactPCNTL\PCNTL($loop);

$pcntl->on(SIGTERM, function() {
        exit(0);
    });
$loop->run();
