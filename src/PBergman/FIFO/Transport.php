<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\FIFO;

use PBergman\FIFO\Exception\InvalidArgumentException;
use PBergman\FIFO\Exception\TransportException;
use PBergman\FIFO\Header\AbstractHeader;
use PBergman\FIFO\Header\DataHeader;
use PBergman\FIFO\Header\SignalHeader;
use PBergman\FIFO\Helper\StreamWrapper;
use PBergman\FIFO\Node\DataNode;

/**
 * Class Transport
 *
 * @package PBergman\FIFO
 */
class Transport
{
    /** @var string  */
    protected $fifo;
    /** @var  StreamWrapper */
    protected $stream;
    /** @var bool  */
    protected $autoClose;

    /**
     * @param   string      $file
     * @param   null|string $folder
     * @param   bool        $autoClose
     * @throws TransportException
     */
    function __construct($file, $folder = null, $autoClose = true)
    {
        $this->intializeFile($file, $folder);
        $this->autoClose = $autoClose;
        $this->stream = new StreamWrapper(fopen($this->fifo, 'r+'));
        $this->stream->setBlocking(false);
    }

    /**
     * check, create and validate the fifo file/folder
     *
     * @param string        $file
     * @param string|null   $folder
     *
     * @throws TransportException
     */
    protected function intializeFile($file, $folder = null)
    {
        if (is_null($folder)) {
            $folder = sys_get_temp_dir();
        }

        if (!is_dir($folder)) {
            if (false === mkdir($folder , 0777, true)) {
                throw TransportException::couldNotCreateFolder($folder);
            }
        }

        $sStrRevFolder = strrev($folder);
        if ($sStrRevFolder[0] === '/') {
            $folder = substr($folder, 0, -1);
        }

        $this->fifo = sprintf('%s/%s', $folder, $file);

        if (!file_exists($this->fifo)) {
            if (false === posix_mkfifo($this->fifo, 0600)) {
                throw TransportException::posixError();
            }
        }

        if (false == (stat($this->fifo)['mode'] & 0010000)) {
            throw TransportException::fileIsNotANamedPipe($this->fifo);
        }
    }

    /**
     * @inheritdoc
     */
    function __destruct()
    {
        if ($this->autoClose) {
            $this->clear(true);
        }
    }

    /**
     * Close resource and remove pipe
     */
    public function clear()
    {
        $this->stream->close();

        if (file_exists($this->fifo)) {
            unlink($this->fifo);
        }
    }

    /**
     * @param   $chunk
     * @return  string
     */
    protected function packChuck($chunk, $length)
    {
        return pack(sprintf('Sa4a%s', $length), $length, hash('crc32b', $chunk, true), $chunk);
    }

    /**
     * write mixed data to fifo handler
     *
     * @param   mixed $data
     * @param   bool $compress
     * @return  int
     * @throws  InvalidArgumentException|Exception\StreamException
     */
    public function write($data, $compress = true)
    {
        $maxSize = pow(2,16) - 1; // Max for chunks of unsigned 16 bit parts
        $header = new DataHeader(posix_getpid());

        switch (gettype($data)) {
            case 'array':
            case 'object':
                    $data = serialize($data);
                    $header->setSerialized(1);
                break;
            case 'resource':
            case 'unknown type':
                throw new InvalidArgumentException(sprintf('Unsupported type: "%s"', gettype($data)));

        }

        if ($compress) {
            $data = gzcompress($data, 9);
        }

        $header
            ->setCompressed($compress)
            ->setChunkCount(ceil((strlen($data)/$maxSize)));

        return $this->stream->lock(function(StreamWrapper $s) use ($header, $data, $maxSize){
            $bytes = $s->write((string) $header);
            foreach (str_split($data, $maxSize) as $part) {
                $bytes += $s->write($this->packChuck($part, strlen($part)));
            }
            $s->flush();
            return $bytes;
        });

    }

    /**
     * @return false|DataNode
     * @throws Exception\StreamException
     */
    public function read()
    {
        if (false === (bool) $this->stream->select()) {
            return false;
        }

        return $this->stream->lock(function(StreamWrapper $s){

            $header = unpack('Ctype/Spid', $s->read(3));
            $return = [];

            switch ($header['type']) {
                case AbstractHeader::TYPE_DATA:
                    $header = new DataHeader($header['pid']);
                    $header
                        ->setSerialized(unpack('C', $s->read(1))[1])
                        ->setCompressed(unpack('C', $s->read(1))[1])
                        ->setChunkCount(unpack('C', $s->read(1))[1]);
                    $data = null;
                    for ($i = 0; $i < $header->getChunkCount(); $i++) {
                        $info = unpack('Slength/a4crc', $s->read(6));
                        $part = $s->read($info['length']);
                        $crc = hash('crc32b', $part, true);
                        if ($info['crc'] !==  $crc) {
                            throw TransportException::checksumMisMatch($info['crc'], $crc);
                        }
                        $data .= $part;
                    }
                    if ($header->isCompressed()) {
                        $data = gzuncompress($data);
                    }

                    if ($header->isSerialized()) {
                        $data = unserialize($data);
                    }
                    $return = new DataNode($data, $header);

                    break;
                case AbstractHeader::TYPE_SIGNAL:
                    $return = new DataNode(
                        unpack('C', $s->read(1))[1],
                        new SignalHeader($header['pid'])
                    );
                    break;
            }
            return $return;

        }, LOCK_SH);
    }

    /**
     * @param   $signal
     * @return  array|string
     */
    public function signal($signal)
    {
        return $this->stream->lock(function(StreamWrapper $s) use ($signal) {
            $bytes = $s->write(pack('CSC', AbstractHeader::TYPE_SIGNAL, posix_getpid(), $signal));
            $s->flush();
            return $bytes;
        });
    }
}
