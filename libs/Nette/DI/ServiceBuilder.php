<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette\DI
 */







/**
 * Basic service builder.
 *
 * @author     David Grudl
 */
class NServiceBuilder extends NObject implements IServiceBuilder
{
	/** @var string */
	private $class;



	public function __construct($class)
	{
		$this->class = $class;
	}



	public function getClass()
	{
		return $this->class;
	}



	public function createService(IDiContainer $container)
	{
		if (!class_exists($this->class)) {
			throw new InvalidStateException("Cannot instantiate service, class '$this->class' not found.");
		}
		return new $this->class;
	}

}
