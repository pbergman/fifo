<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\FIFO\Header;

/**
 * Class SignalHeader
 *
 * @package PBergman\FIFO\Header
 */
class SignalHeader extends AbstractHeader
{
    /**
     * @param int|null $pid
     */
    function __construct($pid = null)
    {
        parent::__construct(self::TYPE_SIGNAL, $pid);
    }

    /**
     * Should return binary representation of header
     *
     * @return string
     */
    function __toString()
    {
        return pack(
            'CS',
            $this->type,
            $this->pid
        );
    }
}