<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette\Caching\Storages
 */



/**
 * Cache journal provider.
 *
 * @author     Jan Smitka
 * @package Nette\Caching\Storages
 */
interface ICacheJournal
{

	/**
	 * Writes entry information into the journal.
	 * @param  string $key
	 * @param  array  $dependencies
	 * @return void
	 */
	function write($key, array $dependencies);


	/**
	 * Cleans entries from journal.
	 * @param  array  $conditions
	 * @return array of removed items or NULL when performing a full cleanup
	 */
	function clean(array $conditions);

}
