<?php
namespace Christiaan\StreamProcess;

class Exception extends \Exception
{
    const ALREADY_OPEN = 1;
    const NOT_OPEN = 2;
    const OPEN_FAILED = 3;
    const NOT_RUNNING = 4;
}