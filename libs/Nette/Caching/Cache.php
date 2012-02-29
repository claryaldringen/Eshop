<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette\Caching
 */







/**
 * Implements the cache for a application.
 *
 * @author     David Grudl
 */
class NCache extends NObject implements ArrayAccess
{
	/** dependency */
	const PRIORITY = 'priority',
		EXPIRATION = 'expire',
		EXPIRE = 'expire',
		SLIDING = 'sliding',
		TAGS = 'tags',
		FILES = 'files',
		ITEMS = 'items',
		CONSTS = 'consts',
		CALLBACKS = 'callbacks',
		ALL = 'all';

	/** @internal */
	const NAMESPACE_SEPARATOR = "\x00";

	/** @var ICacheStorage */
	private $storage;

	/** @var string */
	private $namespace;

	/** @var string  last query cache */
	private $key;

	/** @var mixed  last query cache */
	private $data;



	public function __construct(ICacheStorage $storage, $namespace = NULL)
	{
		$this->storage = $storage;
		$this->namespace = $namespace . self::NAMESPACE_SEPARATOR;
	}



	/**
	 * Returns cache storage.
	 * @return ICacheStorage
	 */
	public function getStorage()
	{
		return $this->storage;
	}



	/**
	 * Returns cache namespace.
	 * @return string
	 */
	public function getNamespace()
	{
		return (string) substr($this->namespace, 0, -1);
	}



	/**
	 * Returns new nested cache object.
	 * @param  string
	 * @return NCache
	 */
	public function derive($namespace)
	{
		$derived = new self($this->storage, $this->namespace . $namespace);
		return $derived;
	}



	/**
	 * Discards the internal cache.
	 * @return void
	 */
	public function release()
	{
		$this->key = $this->data = NULL;
	}



	/**
	 * Retrieves the specified item from the cache or returns NULL if the key is not found.
	 * @param  mixed key
	 * @return mixed|NULL
	 */
	public function load($key)
	{
		$key = is_scalar($key) ? (string) $key : serialize($key);
		if ($this->key === $key) {
			return $this->data;
		}
		$this->key = $key;
		$this->data = $this->storage->read($this->namespace . md5($key));
		return $this->data;
	}



	/**
	 * Writes item into the cache.
	 * Dependencies are:
	 * - NCache::PRIORITY => (int) priority
	 * - NCache::EXPIRATION => (timestamp) expiration
	 * - NCache::SLIDING => (bool) use sliding expiration?
	 * - NCache::TAGS => (array) tags
	 * - NCache::FILES => (array|string) file names
	 * - NCache::ITEMS => (array|string) cache items
	 * - NCache::CONSTS => (array|string) cache items
	 *
	 * @param  mixed  key
	 * @param  mixed  value
	 * @param  array  dependencies
	 * @return mixed  value itself
	 * @throws InvalidArgumentException
	 */
	public function save($key, $data, array $dp = NULL)
	{
		$this->key = is_scalar($key) ? (string) $key : serialize($key);
		$key = $this->namespace . md5($this->key);

		// convert expire into relative amount of seconds
		if (isset($dp[NCache::EXPIRATION])) {
			$dp[NCache::EXPIRATION] = NDateTime53::from($dp[NCache::EXPIRATION])->format('U') - time();
		}

		// convert FILES into CALLBACKS
		if (isset($dp[self::FILES])) {
			//clearstatcache();
			foreach ((array) $dp[self::FILES] as $item) {
				$dp[self::CALLBACKS][] = array(array(__CLASS__, 'checkFile'), $item, @filemtime($item)); // @ - stat may fail
			}
			unset($dp[self::FILES]);
		}

		// add namespaces to items
		if (isset($dp[self::ITEMS])) {
			$dp[self::ITEMS] = (array) $dp[self::ITEMS];
			foreach ($dp[self::ITEMS] as $k => $item) {
				$dp[self::ITEMS][$k] = $this->namespace . md5(is_scalar($item) ? $item : serialize($item));
			}
		}

		// convert CONSTS into CALLBACKS
		if (isset($dp[self::CONSTS])) {
			foreach ((array) $dp[self::CONSTS] as $item) {
				$dp[self::CALLBACKS][] = array(array(__CLASS__, 'checkConst'), $item, constant($item));
			}
			unset($dp[self::CONSTS]);
		}

		if ($data instanceof NCallback || $data instanceof Closure) {
			NCriticalSection::enter();
			$data = $data->__invoke();
			NCriticalSection::leave();
		}

		if (is_object($data)) {
			$dp[self::CALLBACKS][] = array(array(__CLASS__, 'checkSerializationVersion'), get_class($data),
				NClassReflection::from($data)->getAnnotation('serializationVersion'));
		}

		$this->data = $data;
		if ($data === NULL) {
			$this->storage->remove($key);
		} else {
			$this->storage->write($key, $data, (array) $dp);
		}
		return $data;
	}



	/**
	 * Removes items from the cache by conditions.
	 * Conditions are:
	 * - NCache::PRIORITY => (int) priority
	 * - NCache::TAGS => (array) tags
	 * - NCache::ALL => TRUE
	 *
	 * @param  array
	 * @return void
	 */
	public function clean(array $conds = NULL)
	{
		$this->release();
		$this->storage->clean((array) $conds);
	}



	/**
	 * Caches results of function/method calls.
	 * @param  mixed
	 * @return mixed
	 */
	public function call($function)
	{
		$key = func_get_args();
		if ($this->load($key) === NULL) {
			array_shift($key);
			return $this->save($this->key, call_user_func_array($function, $key));
		} else {
			return $this->data;
		}
	}



	/**
	 * Starts the output cache.
	 * @param  mixed  key
	 * @return NCachingHelper|NULL
	 */
	public function start($key)
	{
		if ($this->offsetGet($key) === NULL) {
			return new NCachingHelper($this, $key);
		} else {
			echo $this->data;
		}
	}



	/********************* interface ArrayAccess ****************d*g**/



	/**
	 * Inserts (replaces) item into the cache (ArrayAccess implementation).
	 * @param  mixed key
	 * @param  mixed
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function offsetSet($key, $data)
	{
		$this->save($key, $data);
	}



	/**
	 * Retrieves the specified item from the cache or NULL if the key is not found (ArrayAccess implementation).
	 * @param  mixed key
	 * @return mixed|NULL
	 * @throws InvalidArgumentException
	 */
	public function offsetGet($key)
	{
		return $this->load($key);
	}



	/**
	 * Exists item in cache? (ArrayAccess implementation).
	 * @param  mixed key
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function offsetExists($key)
	{
		return $this->load($key) !== NULL;
	}



	/**
	 * Removes the specified item from the cache.
	 * @param  mixed key
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function offsetUnset($key)
	{
		$this->save($key, NULL);
	}



	/********************* dependency checkers ****************d*g**/



	/**
	 * Checks CALLBACKS dependencies.
	 * @param  array
	 * @return bool
	 */
	public static function checkCallbacks($callbacks)
	{
		foreach ($callbacks as $callback) {
			$func = array_shift($callback);
			if (!call_user_func_array($func, $callback)) {
				return FALSE;
			}
		}
		return TRUE;
	}



	/**
	 * Checks CONSTS dependency.
	 * @param  string
	 * @param  mixed
	 * @return bool
	 */
	private static function checkConst($const, $value)
	{
		return defined($const) && constant($const) === $value;
	}



	/**
	 * Checks FILES dependency.
	 * @param  string
	 * @param  int
	 * @return bool
	 */
	private static function checkFile($file, $time)
	{
		return @filemtime($file) == $time; // @ - stat may fail
	}



	/**
	 * Checks object @serializationVersion label.
	 * @param  string
	 * @param  mixed
	 * @return bool
	 */
	private static function checkSerializationVersion($class, $value)
	{
		return NClassReflection::from($class)->getAnnotation('serializationVersion') === $value;
	}

}
