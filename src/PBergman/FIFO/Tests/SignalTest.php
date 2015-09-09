<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace PBergman\FIFO\Tests;

use PBergman\FIFO\Transport;

class SignalTest extends \PHPUnit_Framework_TestCase
{
    public function testInputOutput()
    {
        $fifo = new Transport('test.tmp');

        foreach ([SIGINT, SIGTERM, SIGALRM, SIGHUP] as $signal) {
            $fifo->signal($signal);
            $result = $fifo->read();
            $this->assertInstanceOf('PBergman\FIFO\Header\AbstractHeader', $result->getHeader());
            $this->assertInstanceOf('PBergman\FIFO\Node\DataNode', $result);
            $this->assertEquals($result->getData(), $signal);
        }
    }

    public function testFork()
    {
        $fifo = new Transport(posix_getpid(), null, false);

        for ($i = 0; $i < 10; $i++) {
            $pid = pcntl_fork();
            if ($pid === 0) {
                $fifo->signal($i);
                usleep(40000);
                exit(0);
            } else {
                usleep(20000);
                $result = $fifo->read();
                $this->assertEquals($result->getHeader()->getPid(), $pid);
                $this->assertEquals($result->getData(), $i);
            }
        }
    }
}