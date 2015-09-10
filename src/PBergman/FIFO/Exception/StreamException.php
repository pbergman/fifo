<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\FIFO\Exception;

/**
 * Class StreamException
 *
 * @package PBergman\FIFO\Exception
 */
class StreamException extends \Exception implements ExceptionInterface
{
    /**
     * @return StreamException
     */
    public static function couldNotLock()
    {
        return new self('Unable to obtain lock');

    }

    /**
     * @return StreamException
     */
    public static function couldNotRelease()
    {
        return new self('Unable to release lock');

    }
}