<?php

/**
 * Defines method that must be implemented to allow a component act like a data grid column.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @package    Nette\Extras\DataGrid
 * @version    $Id: IDataGridColumn.php 33 2009-07-11 19:40:41Z mail@romansklenar.cz $
 */
interface IDataGridColumn
{
	/**
	 * Is column orderable?
	 * @return bool
	 */
	function isOrderable();


	/**
	 * Gets header link (order signal)
	 * @param  string
	 * @return string
	 */
	function getOrderLink($dir = NULL);


	/**
	 * Has column filter box?
	 * @return bool
	 */
	function hasFilter();


	/**
	 * Returns column's filter.
	 * @return IDataGridColumnFilter|NULL
	 */
	function getFilter();


	/**
	 * NFormats cell's content.
	 * @param  mixed
	 * @return string
	 */
	function formatContent($value);


	/**
	 * Filters data source.
	 * @param  mixed
	 * @return void
	 */
	function applyFilter($value);

}