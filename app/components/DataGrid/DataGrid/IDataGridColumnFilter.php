<?php

/**
 * Defines method that must be implemented to allow a component act like a data grid column's filter.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @package    Nette\Extras\DataGrid
 * @version    $Id: IDataGridColumnFilter.php 29 2009-06-21 22:32:13Z mail@romansklenar.cz $
 */
interface IDataGridColumnFilter
{
	/**
	 * Returns filter's form element.
	 * @return NFormNControl
	 */
	function getNFormNControl();


	/**
	 * Gets filter's value, if was filtered.
	 * @return string
	 */
	public function getValue();

}