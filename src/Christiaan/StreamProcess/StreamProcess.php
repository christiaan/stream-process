<?php
namespace Christiaan\StreamProcess;

use RuntimeException;

class StreamProcess
{
    private $command;
    private $pipes;
    private $workingDirectory;
    private $env;
    private $blocking;
    private $resource;
    private $returnCode;

    public function __construct(
        $command,
        $workingDirectory = null,
        array $env = null,
        $blocking = false
    ) {
        if (!function_exists('proc_open')) {
            throw new RuntimeException(
                'The StreamProcess class relies on proc_open, ' .
                    'which is not available on your PHP installation.'
            );
        }

        $this->command = $command;
        if ($workingDirectory === null) {
            $workingDirectory = getcwd();
        }
        $this->workingDirectory = $workingDirectory;
        if ($env !== null) {
            $this->setEnv($env);
        }
        $this->pipes = array();
        $this->open();
        $this->setBlocking($blocking);
    }

    public function __destruct()
    {
        if ($this->isOpen()) {
            if ($this->isRunning()) {
                $this->terminate();
            }
            $this->close();
        }
    }

    /**
     * @throws Exception
     */
    private function open()
    {
        if ($this->isOpen()) {
            throw new Exception('Process already opened', Exception::ALREADY_OPEN);
        }

        $spec = array(
            array("pipe", "r"),
            array("pipe", "w"),
            array("pipe", "w")
        );
        $resource = proc_open(
            $this->command,
            $spec,
            $this->pipes,
            $this->workingDirectory,
            $this->env
        );
        if ($this->workaroundLinuxBug()) {
            $status = proc_get_status($resource);
            posix_setpgid($status['pid'], $status['pid']);
        }

        if ($resource === false) {
            throw new Exception(
                sprintf('Unable to proc_open cmd: %s', $this->command),
                Exception::OPEN_FAILED
            );
        }

        $this->resource = $resource;
    }

    /**
     * @param bool $blocking
     */
    public function setBlocking($blocking)
    {
        $this->blocking = (bool) $blocking;
        foreach ($this->pipes as $pipe) {
            stream_set_blocking($pipe, $this->blocking);
        }
    }

    /**
     * @return bool
     */
    public function isBlocking()
    {
        return $this->blocking;
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return is_resource($this->resource);
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        if (!$this->isOpen()) {
            return false;
        }
        $status = $this->getStatus();

        return isset($status['running']) && $status['running'];
    }

    /**
     * @return int exit code of process
     * @throws Exception when process is already closed
     */
    public function close()
    {
        if (!$this->isOpen()) {
            throw new Exception('Trying to close non open process', Exception::NOT_OPEN);
        }

        $status = $this->getStatus();
        if ($status['running']) {
            $this->returnCode = proc_close($this->resource);
        }
        $this->resource = null;

        return $this->returnCode;
    }

    /**
     * @param int $signal
     * @throws Exception when the process is already closed or not running
     */
    public function terminate($signal = 15)
    {
        if (!$this->isOpen()) {
            throw new Exception('Trying to terminate non open process', Exception::NOT_OPEN);
        }
        if (!$this->isRunning()) {
            throw new Exception('Trying to terminate non running process', Exception::NOT_RUNNING);
        }

        foreach ($this->pipes as $stream) {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if ($this->workaroundLinuxBug()) {
            $status = $this->getStatus();
            posix_kill(-$status['pid'], $signal);
        } else {
            proc_terminate($this->resource, $signal);
        }
    }

    /**
     * @return resource
     */
    public function getWriteStream()
    {
        return $this->pipes[0];
    }

    /**
     * @return resource
     */
    public function getReadStream()
    {
        return $this->pipes[1];
    }

    /**
     * @return resource
     */
    public function getErrorStream()
    {
        return $this->pipes[2];
    }

    /**
     * @return array
     */
    private function getStatus()
    {
        $status = proc_get_status($this->resource);
        if (!$status['running'] && $status['exitcode'] != -1) {
            $this->returnCode = (int) $status['exitcode'];
        }

        return $status;
    }

    private function setEnv(array $env)
    {
        $this->env = array();
        foreach ($env as $key => $value) {
            $this->env[(string)$key] = (string)$value;
        }
    }

    private function workaroundLinuxBug()
    {
        return function_exists('posix_setpgid') && function_exists('posix_kill');
    }
}
