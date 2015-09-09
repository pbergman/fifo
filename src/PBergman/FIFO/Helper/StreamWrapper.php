<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\FIFO\Helper;

use \PBergman\FIFO\Exception\StreamException;

/**
 * Class StreamWrapper
 *
 * @package PBergman\FIFO\Helper
 */
class StreamWrapper
{
    /** @var string  */
    protected $file;
    /** @var resource */
    protected $resource;

    /**
     * @inheritdoc
     */
    function __construct($file)
    {
        $this->file = $file;
        $this->openFile($file);
    }

    /**
     * this will do the callable between a lock and release or stream
     *
     * @param   callable $c
     * @param   int $mode
     * @return  mixed
     * @throws  StreamException
     */
    public function lock(callable $c, $mode = LOCK_EX)
    {
        if (false === flock($this->resource, $mode)) {
            throw StreamException::couldNotLock();
        }

        $ret = $c($this);

        if (false === flock($this->resource, LOCK_UN)) {
            throw StreamException::couldNotRelease();
        }
        return $ret;
    }

    /**
     * @param   int  $length
     * @return  null|string
     */
    public function read($length)
    {
        $data = fread($this->resource, $length);
        return (empty($data)) ? null : $data;
    }

    /**
     * @param   mixed $data
     * @param   null  $length
     * @return  int
     */
    public function write($data, $length = null)
    {
        $ret = fwrite($this->resource, $data, is_null($length) ? strlen($data) : $length);
        fflush($this->resource);
        return $ret;
    }

    /**
     * close resource
     */
    public function close()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }

    /**
     * Check if file exists and open the file
     *
     * @param $file
     * @throws
     */
    protected function openFile($file)
    {
        if (!file_exists($file)) {
            throw StreamException::fileNotAccessible($file);
        }

        if (false === ($this->resource = fopen($file, 'r+'))) {
            throw StreamException::couldNotOpenFile($file);
        }

        stream_set_blocking($this->resource, 0);
    }
}
