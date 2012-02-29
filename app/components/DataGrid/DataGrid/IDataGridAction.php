<?php

/**
 * Defines method that must be implemented to allow a component act like a data grid action.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @package    Nette\Extras\DataGrid
 * @version    $Id: IDataGridAction.php 20 2009-05-25 15:05:53Z mail@romansklenar.cz $
 */
interface IDataGridAction
{
	/**
	 * Gets action element template.
	 * @return Html
	 */
	function getHtml();

}