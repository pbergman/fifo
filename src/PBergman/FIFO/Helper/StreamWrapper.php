<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\FIFO\Helper;

use PBergman\FIFO\Exception\InvalidArgumentException;
use PBergman\FIFO\Exception\StreamException;

/**
 * Class StreamWrapper
 *
 * @package PBergman\FIFO\Helper
 */
class StreamWrapper
{
    /** @var resource */
    protected $resource;

    const SELECT_WRITE = 1;
    const SELECT_READ = 2;

    /**
     * @param   resource $resource
     * @throws  InvalidArgumentException
     */
    function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException(sprintf('Expecting a valid resource got: "%s"', gettype($resource)));
        }
        $this->resource = $resource;
    }

    /**
     * this will do the callable between a lock and release of stream
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
        return fread($this->resource, $length);
    }

    /**
     * @param   mixed $data
     * @param   null  $length
     * @return  int
     */
    public function write($data, $length = null)
    {
        return fwrite(
            $this->resource,
            $data,
            is_null($length) ? strlen($data) : $length
        );
    }

    /**
     * Flushes the output to resource
     *
     * @return bool
     */
    public function flush()
    {
        return fflush($this->resource);
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
     * set stream to non-blocking/blocking
     *
     * @param   bool $blocking
     * @return  bool
     */
    public function setBlocking($blocking = true)
    {
        return stream_set_blocking($this->resource, $blocking);
    }

    /**
     * does a select on stream to check if
     * there is something to read or write.
     *
     * @param   int   $mode
     * @param   int   $timeout
     * @return  int
     */
    public function select($mode = self::SELECT_READ, $timeout = 0)
    {
        $args = array(array(),array(),array(),$timeout);

        if (self::SELECT_READ  === (self::SELECT_READ  & $mode)) {
            $args[0][] = $this->resource;
        }

        if (self::SELECT_WRITE === (self::SELECT_WRITE & $mode)) {
            $args[1][] = $this->resource;
        }

        return stream_select($args[0], $args[1], $args[2], $args[3]);
    }
}
