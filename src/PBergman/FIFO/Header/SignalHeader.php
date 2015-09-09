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
     * @inheritdoc
     */
    function __construct()
    {
        $this->type = self::TYPE_SIGNAL;
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