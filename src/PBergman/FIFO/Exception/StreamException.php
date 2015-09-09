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
     * @param $file
     * @return StreamException
     */
    public static function fileNotAccessible($file)
    {
        return new self(sprintf('File "%s" does not exist or is not accessible', $file));
    }

    /**
     * @param $file
     * @return StreamException
     */
    public static function couldNotOpenFile($file)
    {
        return new self(sprintf('Could not open file: "%s"', $file));
    }

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