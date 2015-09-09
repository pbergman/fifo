<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\FIFO\Node;

use PBergman\FIFO\Header\AbstractHeader;

/**
 * Class DataNode
 *
 * @package PBergman\FIFO\Node
 */
class DataNode
{
    /** @var AbstractHeader */
    protected $header;
    /** @var mixed  */
    protected $data;

    /**
     * @param mixed          $data
     * @param AbstractHeader $header
     */
    function __construct($data, AbstractHeader $header)
    {
        $this->header = $header;
        $this->data = $data;
    }

    /**
     * @return AbstractHeader
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}