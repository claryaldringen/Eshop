<?php

require_once dirname(__FILE__) . '/../DataGridColumnFilter.php';



/**
 * Representation of data grid column checkbox filter.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://nettephp.com/extras/datagrid
 * @package    Nette\Extras\DataGrid
 * @version    $Id: CheckboxFilter.php 29 2009-06-21 22:32:13Z mail@romansklenar.cz $
 */
class CheckboxFilter extends DataGridColumnFilter
{
	/**
	 * Returns filter's form element.
	 * @return NFormNControl
	 */
	public function getNFormNControl()
	{
		if ($this->element instanceof NFormNControl) return $this->element;
		$element = new Checkbox($this->getName());

		return $this->element = $element;
	}
}