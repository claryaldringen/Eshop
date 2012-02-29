<?php

require_once dirname(__FILE__) . '/TextFilter.php';



/**
 * Representation of data grid column date filter.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://nettephp.com/extras/datagrid
 * @package    Nette\Extras\DataGrid
 * @version    $Id: DateFilter.php 26 2009-06-20 20:25:00Z mail@romansklenar.cz $
 */
class DateFilter extends TextFilter
{
	/**
	 * Returns filter's form element.
	 * @return NFormNControl
	 */
	public function getNFormNControl()
	{
		parent::getNFormNControl();
		$this->element->getNControlPrototype()->addClass('datepicker');
		return $this->element;
	}
}