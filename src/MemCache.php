<?php

namespace elementary\cache\MemCache;

use elementary\cache\Runtime\RuntimeCache;
use elementary\core\Singleton\SingletonTrait;
use Memcached;
use Psr\SimpleCache\CacheInterface;

class MemCache
{
    use SingletonTrait;

    /**
     * @var Memcached
     */
    protected $memcached = null;

    /**
     * @var CacheInterface
     */
    protected $runtimecache = null;

    /**
     * @var array
     */
    protected $servers = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @link http://php.net/manual/en/memcached.add.php
     * @param string $key
     * @param mixed $value
     * @param int $ttl [optional]
     *
     * @return bool
     */
    public function add($key, $value, $ttl = null)
    {
        $this->getRuntimecache()->delete($key);
        return $this->getMemcached()->add($key, $value, $ttl);
    }

    /**
     * @link http://php.net/manual/en/memcached.set.php
     * @param string $key
     * @param mixed $value
     * @param int $ttl [optional]
     *
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        $this->getRuntimecache()->delete($key);
        return $this->getMemcached()->set($key, $value, $ttl);
    }

    /**
     * @link http://php.net/manual/en/memcached.get.php
     * @param string $key
     * @param callable $cache_cb [optional]
     * @param float $cas_token [optional]
     *
     * @return mixed
     */
    public function get($key, callable $cache_cb = null, &$cas_token = null)
    {
        if (!$this->getRuntimecache()->has($key) || $cache_cb!==null || $cas_token!==null) {
            $result = $this->getMemcached()->get($key, $cache_cb, $cas_token);
            if ($result) {
                $this->getRuntimecache()->set($key, $result);
            }
        }

        return $this->getRuntimecache()->get($key, false);
    }

    /**
     * @link http://php.net/manual/en/memcached.delete.php
     * @param string $key
     * @param int $time [optional]
     *
     * @return bool
     */
    public function delete($key, $time = 0)
    {
        $this->getRuntimecache()->delete($key);
        return $this->getMemcached()->delete($key, $time);
    }

    /**
     * @link http://php.net/manual/en/memcached.flush.php
     * @param int $delay [optional]
     *
     * @return bool
     */
    public function flush($delay = 0)
    {
        if ($this->getMemcached()->flush($delay)) {
            return $this->getRuntimecache()->clear();
        } else {
            return false;
        }
    }

    /**
     * @link http://php.net/manual/en/memcached.setmulti.php
     * @param array $values
     * @param int $ttl [optional]
     *
     * @return bool
     */
    public function setMulti(array $values, $ttl = null)
    {
        $this->getRuntimecache()->deleteMultiple(array_keys($values));
        return $this->getMemcached()->setMulti($values, $ttl);
    }

    /**
     * @link http://php.net/manual/en/memcached.touch.php
     * @param string $key
     * @param int $ttl
     *
     * @return bool
     */
    public function touch($key, $ttl)
    {
        return $this->getMemcached()->touch($key, $ttl);
    }

    /**
     * @link http://php.net/manual/en/memcached.getmulti.php
     * @param array $keys
     * @param array $cas_tokens [optional]
     * @param int $flags [optional]
     *
     * @return mixed
     */
    public function getMulti(array $keys, array &$cas_tokens = null, $flags = null)
    {
        $nkeys = [];
        $ekeys = [];
        foreach ($keys as $key) {
            if (!$this->getRuntimecache()->has($key) || $cas_tokens!==null || $flags!==null) {
                $nkeys[]= $key;
            } else {
                $ekeys[]= $key;
            }
        }

        $eresult = $this->getRuntimecache()->getMultiple($ekeys, false);
        $nresult = $this->getMemcached()->getMulti($nkeys, $cas_tokens, $flags);

        $this->getRuntimecache()->setMultiple($nresult);

        return array_merge($eresult, $nresult);
    }

    /**
     * @link http://php.net/manual/en/memcached.deletemulti.php
     * @param array $keys
     * @param int $time [optional]
     *
     * @return bool
     */
    public function deleteMulti(array $keys, $time = 0)
    {
        $this->getRuntimecache()->deleteMultiple($keys);
        return $this->getMemcached()->deleteMulti($keys, $time);
    }

    /**
     * @return int
     */
    public function getResultCode()
    {
        return $this->getMemcached()->getResultCode();
    }

    /**
     * @return string
     */
    public function getResultMessage()
    {
        return $this->getMemcached()->getResultMessage();
    }

    /**
     * @link http://php.net/manual/en/memcached.increment.php
     * @param string $key
     * @param int $offset [optional]
     * @param int $initial_value [optional]
     * @param int $expiry [optional]
     *
     * @return int
     */
    public function increment($key, $offset = 1, $initial_value = 0, $expiry = 0)
    {
        $this->getRuntimecache()->delete($key);

        if ($this->getMemcached()->getOption(Memcached::OPT_BINARY_PROTOCOL)) {
            return $this->getMemcached()->increment($key, $offset, $initial_value, $expiry);
        } else {
            return $this->getMemcached()->increment($key, $offset);
        }
    }

    /**
     * @link http://php.net/manual/en/memcached.increment.php
     * @param string $key
     * @param int $offset [optional]
     * @param int $initial_value [optional]
     * @param int $expiry [optional]
     *
     * @return int
     */
    public function decrement($key, $offset = 1, $initial_value = 0, $expiry = 0)
    {
        $this->getRuntimecache()->delete($key);
        if ($this->getMemcached()->getOption(Memcached::OPT_BINARY_PROTOCOL)) {
            return $this->getMemcached()->decrement($key, $offset, $initial_value, $expiry);
        } else {
            return $this->getMemcached()->decrement($key, $offset);
        }
    }

    /**
     * @link http://php.net/manual/en/memcached.cas.php
     * @param float $cas_token
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return bool
     */
    public function cas($cas_token, $key, $value, $ttl = null)
    {
        return $this->getMemcached()->cas($cas_token, $key, $value, $ttl);
    }

    /**
     * @link http://php.net/manual/en/memcached.addserver.php
     * @param string $host
     * @param int $port
     * @param int $weight
     *
     * @return $this
     */
    public function addServer($host, $port, $weight = 0)
    {
        return $this->setServers([[$host, $port, $weight]]);
    }

    /**
     * @link http://php.net/manual/en/memcached.addservers.php
     * @param array $servers
     *
     * @return $this
     */
    public function addServers(array $servers)
    {
        return $this->setServers($servers);
    }

    /**
     * @link http://php.net/manual/en/memcached.getstats.php
     *
     * @return array
     */
    public function getStats()
    {
        return $this->getMemcached()->getStats();
    }


    /**
     * @link http://php.net/manual/en/memcached.getallkeys.php
     *
     * @return array
     */
    public function getAllKeys()
    {
        return $this->getMemcached()->getAllKeys();
    }

    /**
     * @link http://php.net/manual/en/memcached.resetserverlist.php
     * @return bool
     */
    public function resetServerList()
    {
        if ($this->getMemcached()->resetServerList()) {
            $this->servers = [];
            return true;
        } else {
            return false;
        }
    }

    /**
     * @link http://php.net/manual/en/memcached.quit.php
     * @return bool
     */
    public function quit()
    {
        if ($this->getMemcached()->quit()) {
            unset($this->memcached);
            $this->memcached = null;
            return $this->getRuntimecache()->clear();
        } else {
            return false;
        }
    }

    /**
     * @return Memcached
     */
    public function getMemcached()
    {
        if ($this->memcached === null) {
            $this->setMemcached(new Memcached());

            $servers = array_values($this->getServers());
            if ($servers) {
                $this->memcached->addServers($servers);
            }

            $options = array_values($this->getOptions());
            if ($options) {
                $this->memcached->setOptions($options);
            }
        }

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

    /**
     * @return CacheInterface
     */
    public function getRuntimecache()
    {
        if ($this->runtimecache === null) {
            $this->setRuntimecache(new RuntimeCache());
        }

        return $this->runtimecache;
    }

    /**
     * @param CacheInterface $runtimecache
     *
     * @return $this
     */
    public function setRuntimecache(CacheInterface $runtimecache)
    {
        $this->runtimecache = $runtimecache;

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
    public function setServers( array $servers)
    {
        $nServers = [];
        foreach ($servers as $server) {
            if (is_array($server)) {
                if (!isset($server[2])) {
                    $server[2] = 0;
                }

                $key = implode('', $server);
                if (!array_key_exists($key, $this->servers)) {
                    $nServers[] = $servers;
                    $this->servers[$key] = $server;
                }
            }
        }

        if ($this->memcached !== null && !empty($nServers)) {
            $this->memcached->addServers($nServers);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @link http://php.net/manual/en/memcached.setoptions.php
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key=>$value) {
            if (!array_key_exists($key, $this->servers)) {
                $options[$key] = $value;
                $this->options[$key] = $value;
            }
        }

        if ($this->memcached !== null && !empty($options)) {
            $this->memcached->setOptions($options);
        }

        return $this;
    }
}