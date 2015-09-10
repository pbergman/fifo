<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\FIFO;

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
        if (is_null($folder)) {
            $folder = sys_get_temp_dir();
        }

        if (!is_dir($folder)) {
            if (false === mkdir($folder , 0777, true)) {
                throw TransportException::couldNotCreateFolder($folder);
            }
        }

        if (strrev($folder)[0] === '/') {
            $folder = substr($folder, 0, -1);
        }

        $this->fifo = sprintf('%s/%s', $folder, $file);
        $this->autoClose = $autoClose;

        if (!file_exists($this->fifo)) {
            if (false === posix_mkfifo($this->fifo, 0600)) {
                throw TransportException::posixError();
            }
        }

        if (false == (stat($this->fifo)['mode'] & 0010000)) {
            throw TransportException::fileIsNotANamedPipe($this->fifo);
        }

        $this->stream = new StreamWrapper($this->fifo);
    }

    /**
     * @inheritdoc
     */
    function __destruct()
    {
        if ($this->autoClose) {
            $this->stream->close();
        }
    }

    /**
     * @param   $chunk
     * @return  string
     */
    protected function packChuck($chunk)
    {
        return pack('Sa4', strlen($chunk), hash('crc32b', $chunk, true)) . $chunk;
    }

    /**
     * write mixed data to fifo handler
     *
     * @param mixed     $data
     * @param bool      $serialize
     * @param bool      $compress
     * @return int
     */
    public function write($data, $serialize = true, $compress = true)
    {
        if ($serialize) {
            $data = serialize($data);
        }

        if ($compress) {
            $data = gzcompress($data, 9);
        }

        $maxSize = pow(2,16) - 1; // Max for chunks of unsigned 16 bit parts
        $header = new DataHeader(posix_getpid());
        $header
            ->setSerialized($serialize)
            ->setCompressed($compress)
            ->setChunkCount(ceil((strlen($data)/$maxSize)));


        $ret = $this->stream->lock(function(StreamWrapper $s) use ($header, $data, $maxSize){
            $ret = $s->write((string) $header);
            foreach (str_split($data, $maxSize) as $part) {
                $ret += $s->write($this->packChuck($part));
            }
            return $ret;
        });

        return $ret;
    }

    /**
     * @return false|DataNode
     * @throws Exception\StreamException
     */
    public function read()
    {
        return $this->stream->lock(function(StreamWrapper $s){

            if (null !== ($data = $s->read(3))) {

                $header = unpack('Ctype/Spid', $data);
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
            } else {
                return false;
            }
        }, LOCK_SH);
    }

    /**
     * @param   $signal
     * @return  array|string
     */
    public function signal($signal)
    {
        return $this->stream->lock(function(StreamWrapper $s) use ($signal) {
            return $s->write(pack('CSC', AbstractHeader::TYPE_SIGNAL, posix_getpid(), $signal));
        });
    }

}
