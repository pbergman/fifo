### FIFO (named pipes)
A simple wrapper around [posix_mkfifo](http://php.net/manual/en/function.posix-mkfifo.php) that can send signals and
mixed object with ipc. Difference between signals and data is that data will verified and signal only will return the 
header (with pid and type) and the signal and is basically a stripped version of data to send integers.

###usage:

```
$object = new \stdClass();
$object->foo = 'bar';
$object->bar = 'foo';

$transport = new \PBergman\FIFO\Transport(posix_getpid());
$transport->write($object);
$transport->signal(SIGINT);

var_dump($transport->read()) // Will return $object;
var_dump($transport->read()) // Will return SIGINT;
var_dump($transport->read()) // Will return false;

```