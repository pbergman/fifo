<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\FIFO\Exception;

/**
 * Class TransportException
 *
 * @package PBergman\FIFO\Exception
 */
class TransportException extends \Exception implements ExceptionInterface
{
    /**
     * @param string    $a
     * @param string    $b
     * @return TransportException
     */
    public static function checksumMisMatch($a, $b)
    {
        return new self(sprintf('Checksum does not match, 0x%s !== 0x%s', bin2hex($a),  bin2hex($b)));
    }

    /**
     * @return TransportException
     */
    public static function posixError()
    {
        return new self(posix_get_last_error());
    }

    /**
     * @param   string  $folder
     * @return  TransportException
     */
    public static function couldNotCreateFolder($folder)
    {
        return new self(sprintf('Could not create folder "%s"', $folder));
    }

    /**
     * @param   string  $file
     * @return  TransportException
     */
    public static function fileIsNotANamedPipe($file)
    {
        return new self(sprintf('File "%s" is not a named pipe (fifo)', $file));
    }
}
