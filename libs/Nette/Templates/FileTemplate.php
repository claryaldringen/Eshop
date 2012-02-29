<?php

/**
 * This file is part of the Nette Framework.
 *
 * Copyright (c) 2004, 2010 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license", and/or
 * GPL license. For more information please see http://nette.org
 * @package Nette\Templates
 */



/**
 * NTemplate stored in file.
 *
 * @author     David Grudl
 */
class NFileTemplate extends NTemplate implements IFileTemplate
{
	/** @var int */
	public static $cacheExpire = NULL;

	/** @var ICacheStorage */
	private static $cacheStorage;

	/** @var string */
	private $file;



	/**
	 * Constructor.
	 * @param  string  template file path
	 */
	public function __construct($file = NULL)
	{
		if ($file !== NULL) {
			$this->setFile($file);
		}
	}



	/**
	 * Sets the path to the template file.
	 * @param  string  template file path
	 * @return NFileTemplate  provides a fluent interface
	 */
	public function setFile($file)
	{
		$this->file = realpath($file);
		if (!$this->file) {
			throw new FileNotFoundException("Missing template file '$file'.");
		}
		return $this;
	}



	/**
	 * Returns the path to the template file.
	 * @return string  template file path
	 */
	public function getFile()
	{
		return $this->file;
	}



	/********************* rendering ****************d*g**/



	/**
	 * Renders template to output.
	 * @return void
	 */
	public function render()
	{
		if ($this->file == NULL) { // intentionally ==
			throw new InvalidStateException("Template file name was not specified.");
		}

		$this->__set('template', $this);

		$cache = new NCache($storage = $this->getCacheStorage(), 'Nette.FileTemplate');
		if ($storage instanceof NTemplateCacheStorage) {
			$storage->hint = str_replace(dirname(dirname($this->file)), '', $this->file);
		}
		$cached = $content = $cache[$this->file];

		if ($content === NULL) {
			try {
				$content = $this->compile(file_get_contents($this->file));
				$content = "<?php\n\n// source file: $this->file\n\n?>$content";

			} catch (NTemplateException $e) {
				$e->setSourceFile($this->file);
				throw $e;
			}

			$cache->save(
				$this->file,
				$content,
				array(
					NCache::FILES => $this->file,
					NCache::EXPIRATION => self::$cacheExpire,
					NCache::CONSTS => 'NFramework::REVISION',
				)
			);
			$cache->release();
			$cached = $cache[$this->file];
		}

		if ($cached !== NULL && $storage instanceof NTemplateCacheStorage) {
			NLimitedScope::load($cached['file'], $this->getParams());
			fclose($cached['handle']);

		} else {
			NLimitedScope::evaluate($content, $this->getParams());
		}
	}



	/********************* caching ****************d*g**/



	/**
	 * Set cache storage.
	 * @param  NCache
	 * @return void
	 */
	public static function setCacheStorage(ICacheStorage $storage)
	{
		self::$cacheStorage = $storage;
	}



	/**
	 * @return ICacheStorage
	 */
	public static function getCacheStorage()
	{
		if (self::$cacheStorage === NULL) {
			$dir = NEnvironment::getVariable('tempDir') . '/cache';
			umask(0000);
			@mkdir($dir, 0777); // @ - directory may exists
			self::$cacheStorage = new NTemplateCacheStorage($dir);
		}
		return self::$cacheStorage;
	}

}
