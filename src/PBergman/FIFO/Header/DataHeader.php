<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\FIFO\Header;

/**
 * Class DataHeader
 *
 * @package PBergman\FIFO\Header
 */
class DataHeader extends AbstractHeader
{
    /** @var int */
    protected $serialized;
    /** @var int */
    protected $compressed;
    /** @var int  */
    protected $chunkCount;

    /**
     * @param int|null $pid
     */
    function __construct($pid = null)
    {
        parent::__construct(self::TYPE_DATA, $pid);
    }

    /**
     * Should return binary representation of header
     *
     * @return string
     */
    function __toString()
    {
        return pack(
            'CSC*',
            $this->type,
            $this->pid,
            $this->serialized,
            $this->compressed,
            $this->chunkCount
        );
    }

    /**
     * @param   int $compressed
     * @return  $this;
     */
    public function setCompressed($compressed)
    {
        $this->compressed = (int) $compressed;
        return $this;
    }

    /**
     * @param   int $serialized
     * @return  $this;
     */
    public function setSerialized($serialized)
    {
        $this->serialized = (int) $serialized;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSerialized()
    {
        return (bool) $this->serialized;
    }

    /**
     * @return bool
     */
    public function isCompressed()
    {
        return (bool) $this->compressed;
    }

    /**
     * @return int
     */
    public function getChunkCount()
    {
        return $this->chunkCount;
    }

    /**
     * @param   int $chunk_count
     * @return  $this;
     */
    public function setChunkCount($chunkCount)
    {
        $this->chunkCount = (int) $chunkCount;
        return $this;
    }
}