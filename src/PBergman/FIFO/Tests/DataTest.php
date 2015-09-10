<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace PBergman\FIFO\Tests;

use PBergman\FIFO\Transport;

class TreeHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testInputOutput()
    {
        $object = new \stdClass();
        $object->foo = 'bar';
        $object->bar = 'foo';
        $fifo = new Transport('test.tmp');
        $fifo->write($object);
        $result = $fifo->read();
        $this->assertInstanceOf('PBergman\FIFO\Header\AbstractHeader', $result->getHeader());
        $this->assertInstanceOf('PBergman\FIFO\Node\DataNode', $result);
        $this->assertEquals($result->getData()->foo, $object->foo);
        $this->assertEquals($result->getData()->bar, $object->bar);
        $this->assertEquals($result->getHeader()->getPid(), posix_getpid());
        $this->assertFalse($fifo->read());
        $this->assertFalse($fifo->read());
        $this->assertFalse($fifo->read());
    }

    public function testSerialize()
    {
        $fifo = new Transport('test.tmp');
        $data = [
            [1, new \stdClass()],
            [1, []],
            [0, null],
            [0, 'foo bar'],
        ];
        foreach ($data as $info) {
            $fifo->write($info[1]);
        }
        foreach ($data as $info) {
            $result = $fifo->read();
            $this->assertEquals($result->getHeader()->isSerialized(), (bool) $info[0]);
        }
    }

    /**
     * @expectedException              \PBergman\FIFO\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp #^Unsupported type: "\w+"$#
     */
    public function testException()
    {
        $fifo = new Transport('test.tmp');
        $fifo->write(fopen('php://memory', 'r+'));
    }


    public function testFork()
    {
        $fifo = new Transport(posix_getpid(), null, false);

        for ($i = 0; $i < 10; $i++) {
            $pid = pcntl_fork();
            if ($pid === 0) {
                $fifo->write($i);
                usleep(40000);
                exit(0);
            } else {
                usleep(20000);
                $result = $fifo->read();
                $this->assertEquals($result->getHeader()->getPid(), $pid);
                $this->assertEquals($result->getData(), $i);
            }
        }

        $fifo->clear();
    }
}