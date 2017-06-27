<?php

namespace elementary\cache\MemCache\Test;

use elementary\cache\MemCache\MemCache;
use Memcached;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \elementary\cache\MemCache\MemCache
 */
class MemCacheTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MemCache
     */
    protected $memory = null;

    /**
     * @var Memcached
     */
    protected $memcached = null;

    /**
     * @var array
     */
    protected $servers= [];

    /**
     * @test
     * @covers ::getMemcached
     * @covers ::setMemcached
     * @covers ::getRuntimecache
     * @covers ::setRuntimecache
     */
    public function cache()
    {
        $this->assertInstanceOf('\Memcached', $this->getMemory()->getMemcached());
        $this->assertInstanceOf('\Psr\SimpleCache\CacheInterface', $this->getMemory()->getRuntimecache());
    }

    /**
     * @test
     * @covers ::add
     * @depends cache
     */
    public function add()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $this->assertTrue($this->getMemory()->add('test', 123));
        $this->assertEquals(123, $this->getMemcached()->get('test'));

        $this->getMemory()->get('test');
        $this->assertFalse($this->getMemory()->add('test', 123));
        $this->assertFalse($this->getMemory()->getRuntimecache()->has('test'));
    }

    /**
     * @test
     * @covers ::set
     */
    public function set()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $this->assertTrue($this->getMemory()->set('test', 123));
        $this->assertEquals(123, $this->getMemcached()->get('test'));

        $this->assertTrue($this->getMemory()->getRuntimecache()->set('test', 1234));
        $this->getMemory()->set('test', 123);
        $this->assertFalse($this->getMemory()->getRuntimecache()->has('test'));
    }

    /**
     * @test
     * @covers ::get
     */
    public function get()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $this->getMemcached()->set('test', 123);
        $this->assertEquals(123, $this->getMemory()->get('test'));
        $this->assertEquals(123, $this->getMemory()->getRuntimecache()->get('test'));

        $this->getMemory()->getRuntimecache()->set('test', 1234);
        $this->assertEquals(1234, $this->getMemory()->get('test'));

        $token = 0;
        $this->assertEquals(123, $this->getMemory()->get('test', null, $token));
        $this->assertEquals(123, $this->getMemory()->getRuntimecache()->get('test', false));
    }

    /**
     * @test
     * @covers ::delete
     */
    public function delete()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $this->getMemcached()->set('test', 123);
        $this->assertTrue($this->getMemory()->delete('test'));
        $this->assertFalse($this->getMemcached()->get('test'));
    }

    /**
     * @test
     * @covers ::setMulti
     */
    public function setMulti()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $data = ['test' => 123, 'test2' => 1234];
        $this->getMemory()->setMulti($data);
        $this->assertEquals($data, $this->getMemcached()->getMulti(['test', 'test2']));
    }

    /**
     * @test
     * @covers ::getMulti
     */
    public function getMulti()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $data = ['test' => 123, 'test2' => 1234];
        $this->getMemcached()->setMulti($data);
        $this->assertEquals($data, $this->getMemory()->getMulti(['test', 'test2']));
        $this->assertEquals(123, $this->getMemory()->getRuntimecache()->get('test'));

        $this->getMemory()->getRuntimecache()->set('test3', 12345);
        $this->getMemory()->getRuntimecache()->delete('test2');
        $this->assertEquals(['test' => 123, 'test2' => 1234, 'test3' => 12345], $this->getMemory()->getMulti(['test', 'test2', 'test3']));

        $tokens = [];
        $this->assertEquals($data, $this->getMemory()->getMulti(['test', 'test2', 'test3'], $tokens));
    }

    /**
     * @test
     * @covers ::deleteMulti
     */
    public function deleteMulti()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $this->getMemory()->setMulti(['test' => 123, 'test2' => 1234]);
        $this->assertEquals(['test' => true, 'test2' => true], $this->getMemory()->deleteMulti(['test', 'test2']));
        $this->assertFalse($this->getMemcached()->get('test'));
        $this->assertFalse($this->getMemory()->getRuntimecache()->has('test2'));
    }

    /**
     * @test
     * @covers ::increment
     * @covers ::decrement
     */
    public function crement()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $this->getMemcached()->set('test', 123);
        $this->getMemory()->getRuntimecache()->set('test1', 1234);
        $this->assertEquals(125, $this->getMemory()->increment('test', 2));
        $this->assertFalse($this->getMemory()->getRuntimecache()->has('test'));

        $this->getMemcached()->set('test2', 123);
        $this->getMemory()->getRuntimecache()->set('test2', 1234);
        $this->assertEquals(121, $this->getMemory()->decrement('test2', 2));
        $this->assertFalse($this->getMemory()->getRuntimecache()->has('test2'));

        $this->getMemory()->getMemcached()->setOption(Memcached::OPT_BINARY_PROTOCOL,true);
        $this->getMemcached()->deleteMulti(['test', 'test2']);

        $this->assertEquals(123, $this->getMemory()->increment('test', 2, 123));
        $this->assertEquals(125, $this->getMemory()->increment('test', 2, 123));

        $this->assertEquals(123, $this->getMemory()->decrement('test2', 2, 123));
        $this->assertEquals(121, $this->getMemory()->decrement('test2', 2, 123));
    }

    /**
     * @test
     * @covers ::getStats
     */
    public function stats()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $stats = array_pop($this->getMemory()->getStats());
        $this->assertArrayHasKey('version', $stats);
    }
    /**
     * @test
     * @covers ::getResultCode
     * @covers ::getResultMessage
     */
    public function getResult()
    {
        $this->assertInternalType('integer', $this->getMemory()->getResultCode());
        $this->assertInternalType('string', $this->getMemory()->getResultMessage());
    }

    /**
     * @test
     * @covers ::getServers
     * @covers ::setServers
     * @covers ::addServer
     * @covers ::addServers
     * @covers ::resetServerList
     */
    public function servers()
    {
        $mem = new MemCache();
        $mem->addServer('test', 123);
        $this->assertEquals([['test', 123, 0]], array_values($mem->getServers()));

        $mem->addServers([['test', 123], ['test2', 12, 5]]);
        $this->assertEquals([['test', 123, 0], ['test2', 12, 5]], array_values($mem->getServers()));

        $mem->resetServerList();
        $this->assertEquals([], $mem->getServers());
    }

    protected function setUp()
    {
        $this->setMemory(new MemCache())
             ->setMemcached(new Memcached())
             ->setServers(require(__DIR__ .'/config.php'));

        $this->getMemory()->addServers($this->getServers());
        $this->getMemcached()->addServers($this->getServers());
    }

    protected function tearDown()
    {
        $this->getMemcached()->delete('test');
        $this->getMemcached()->delete('test2');
    }

    /**
     * @return MemCache
     */
    public function getMemory()
    {
        return $this->memory;
    }

    /**
     * @param MemCache $memory
     *
     * @return $this
     */
    public function setMemory($memory)
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * @return array
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * @param array $servers
     *
     * @return $this
     */
    public function setServers($servers)
    {
        $this->servers = $servers;

        return $this;
    }

    /**
     * @return Memcached
     */
    public function getMemcached()
    {
        return $this->memcached;
    }

    /**
     * @param Memcached $cache
     *
     * @return $this
     */
    public function setMemcached(Memcached $cache)
    {
        $this->memcached = $cache;

        return $this;
    }
}
