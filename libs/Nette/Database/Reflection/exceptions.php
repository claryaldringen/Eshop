<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette\Database\Reflection
 */



/**
 * Reference not found exception.
 * @package Nette\Database\Reflection
 */
class NMissingReferenceException extends PDOException
{
}



/**
 * Ambiguous reference key exception.
 * @package Nette\Database\Reflection
 */
class NAmbiguousReferenceKeyException extends PDOException
{
}
