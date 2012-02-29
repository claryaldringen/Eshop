<?php

/**
 * Defines method that must implement data grid rendered.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @package    Nette\Extras\DataGrid
 * @version    $Id: IDataGridRenderer.php 29 2009-06-21 22:32:13Z mail@romansklenar.cz $
 */
interface IDataGridRenderer
{
	/**
	 * Provides complete data grid rendering.
	 * @param  DataGrid
	 * @return string
	 */
	function render(DataGrid $dataGrid);

}