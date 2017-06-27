<?php

namespace elementary\cache\MemCache\Test;

use elementary\cache\MemCache\MemCache;
use Memcached;
use PHPUnit_Framework_TestCase;

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
     */
    public function add()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $this->assertTrue($this->getMemory()->add('test', 123));
        $this->assertEquals(123, $this->getMemcached()->get('test'));

        $this->getMemory()->get('test');
        $this->assertFalse($this->getMemory()->add('test', 123));
        $this->assertFalse($this->getMemory()->getRuntimecache()->get('test', false));
    }

    /**
     * @test
     */
    public function set()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $this->assertTrue($this->getMemory()->set('test', 123));
        $this->assertEquals(123, $this->getMemcached()->get('test'));

        $this->getMemory()->get('test');
        $this->getMemory()->set('test', 123);
        $this->assertFalse($this->getMemory()->getRuntimecache()->get('test', false));
    }

    /**
     * @test
     */
    public function get()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $this->getMemcached()->set('test', 123);
        $this->assertEquals(123, $this->getMemory()->get('test'));
        $this->assertEquals(123, $this->getMemory()->getRuntimecache()->get('test', false));

        $this->getMemory()->getRuntimecache()->set('test', 1234);
        $this->assertEquals(1234, $this->getMemory()->get('test'));

        $token = 0;
        $this->assertEquals(123, $this->getMemory()->get('test', null, $token));
        $this->assertEquals(123, $this->getMemory()->getRuntimecache()->get('test', false));
    }

    /**
     * @test
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
     */
    public function getMulti()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $data = ['test' => 123, 'test2' => 1234];
        $this->getMemcached()->setMulti($data);
        $this->assertEquals($data, $this->getMemory()->getMulti(['test', 'test2']));
    }

    /**
     * @test
     */
    public function deleteMulti()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $this->getMemory()->setMulti(['test' => 123, 'test2' => 1234]);
        $this->assertTrue($this->getMemcached()->deleteMulti(['test', 'test2']));
        $this->assertFalse($this->getMemcached()->get('test'));
        $this->assertFalse($this->getMemory()->getRuntimecache()->get('test2'));
    }

    /**
     * @test
     */
    public function crement()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $this->assertEquals(125, $this->getMemory()->increment('test', 2, 123));
        $this->assertEquals(127, $this->getMemory()->increment('test', 2, 123));
        $this->assertEquals(121, $this->getMemory()->decrement('test2', 2, 123));
        $this->assertEquals(119, $this->getMemory()->decrement('test2', 2, 123));
    }

    /**
     * @test
     */
    public function stats()
    {
        fwrite(STDOUT, "\n". __METHOD__);

        $stats = array_pop($this->getMemcached()->getStats());
        $this->assertArrayHasKey('version', $stats);
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
