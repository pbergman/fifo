<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\FIFO\Header;

/**
 * Class AbstractHeader
 *
 * @package PBergman\FIFO\Header
 */
abstract class AbstractHeader
{
    const TYPE_SIGNAL = 1;
    const TYPE_DATA = 2;

    /** @var int */
    protected $type;
    /** @var int */
    protected $pid;

    /**
     * Should return binary representation of header
     *
     * @return string
     */
    abstract function __toString();

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param   int $type
     * @return  $this;
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param   int $pid
     * @return  $this;
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
        return $this;
    }
}