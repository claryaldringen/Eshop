<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette\Config
 */







/**
 * Configuration storage.
 *
 * @author     David Grudl
 */
class NConfig
{
	/** @var array */
	private static $extensions = array(
		'ini' => 'NConfigAdapterIni',
		'neon' => 'NConfigAdapterNeon',
	);



	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new NStaticClassException;
	}



	/**
	 * Registers adapter for given file extension.
	 * @param  string  file extension
	 * @param  string  class name (IConfigAdapter)
	 * @return void
	 */
	public static function registerExtension($extension, $class)
	{
		if (!class_exists($class)) {
			throw new InvalidArgumentException("Class '$class' was not found.");
		}

		if (!NClassReflection::from($class)->implementsInterface('IConfigAdapter')) {
			throw new InvalidArgumentException("Configuration adapter '$class' is not IConfigAdapter implementor.");
		}

		self::$extensions[strtolower($extension)] = $class;
	}



	/**
	 * Creates new configuration object from file.
	 * @param  string  file name
	 * @param  string  section to load
	 * @return array
	 */
	public static function fromFile($file, $section = NULL)
	{
		$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		if (!isset(self::$extensions[$extension])) {
			throw new InvalidArgumentException("Unknown file extension '$file'.");
		}

		$data = call_user_func(array(self::$extensions[$extension], 'load'), $file, $section);
		if ($section) {
			if (!isset($data[$section]) || !is_array($data[$section])) {
				throw new InvalidStateException("There is not section [$section] in file '$file'.");
			}
			$data = $data[$section];
		}
		return $data;
	}



	/**
	 * Save configuration to file.
	 * @param  mixed
	 * @param  string  file
	 * @return void
	 */
	public static function save($config, $file)
	{
		$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		if (!isset(self::$extensions[$extension])) {
			throw new InvalidArgumentException("Unknown file extension '$file'.");
		}
		return call_user_func(array(self::$extensions[$extension], 'save'), $config, $file);
	}

}
