<?php
namespace Christiaan\StreamProcess;

use PHPUnit\Framework\TestCase;

function function_exists($function)
{
    return StreamProcessTest::$functionExists;
}

class StreamProcessTest extends TestCase
{
    public static $functionExists = true;

    protected function setUp()
    {
        self::$functionExists = \function_exists('proc_open');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testProcOpenFunctionDoesNotExist()
    {
        self::$functionExists = false;
        new StreamProcess('php -v');
    }

    public function testUseReadStream()
    {
        $process = new StreamProcess('echo hoi');
        $this->assertTrue($process->isRunning());
        $this->waitTillStop($process);
        $this->assertFalse($process->isRunning());
        $out = fread($process->getReadStream(), 4092);
        $this->assertEquals('hoi'.PHP_EOL, $out);
    }

    public function testCommunicationWithEchoerWithBlocking()
    {
        $echoer = $this->getEchoer();
        $echoer->setBlocking(true);

        $write = $echoer->getWriteStream();
        $this->assertTrue($echoer->isRunning());

        fwrite($write, 'Hoi'.PHP_EOL);
        $received = fread($echoer->getReadStream(), 4094);
        $error = fread($echoer->getErrorStream(), 4094);

        $this->assertEquals('Hoi' . PHP_EOL, $received);
        $this->assertEquals('Hoi' . PHP_EOL, $error);

        $echoer->terminate();
        $echoer->close();

        $this->assertFalse($echoer->isRunning());
        $this->assertFalse($echoer->isOpen());
    }

    public function testSetEnv()
    {
        $process = new StreamProcess('echo $ENV_VARIABLE', null, array('ENV_VARIABLE' => 'test'));
        $this->waitTillStop($process);
        $out = fread($process->getReadStream(), 4092);
        $this->assertEquals('test' . PHP_EOL, $out);
    }

    public function testClosingWriteStreamEarlyGivesNoError()
    {
        $echoer = $this->getEchoer();
        $this->assertTrue($echoer->isRunning());

        fclose($echoer->getWriteStream());

        $echoer->terminate();
        $echoer->close();
    }

    private function waitTillStop(StreamProcess $process)
    {
        $i = 0;
        while ($process->isRunning() && $i < 100) {
            usleep(1000);
            $i += 1;
        }
    }

    /**
     * @return StreamProcess
     */
    private function getEchoer()
    {
        return new StreamProcess('php ' . __DIR__ . '/echoer.php');
    }
}
