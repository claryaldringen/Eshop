<?php

require_once dirname(__FILE__) . '/../DataGridColumn.php';



/**
 * Representation of data grid action column.
 * If you want to write your own implementation you must inherit this class.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://nettephp.com/extras/datagrid
 * @package    Nette\Extras\DataGrid
 * @version    $Id: ActionColumn.php 42 2009-07-27 13:55:50Z mail@romansklenar.cz $
 */
class ActionColumn extends DataGridColumn implements ArrayAccess
{

	/**
	 * Action column constructor.
	 * @param  string  column's textual caption
	 * @return void
	 */
	public function __construct($caption = 'Actions')
	{
		parent::__construct($caption);
		$this->addNComponent(new NComponentContainer, 'actions');
		$this->removeNComponent($this->getNComponent('filters'));
		$this->orderable = FALSE;
	}


	/**
	 * Has column filter box?
	 * @return bool
	 */
	public function hasFilter()
	{
		return FALSE;
	}


	/**
	 * Returns column's filter.
	 * @param  bool   throw exception if component doesn't exist?
	 * @return IDataGridColumnFilter|NULL
	 * @throws InvalidStateException
	 */
	public function getFilter($need = TRUE)
	{
		if ($need == TRUE) {
			throw new InvalidStateException("ActionColumn cannot has filter.");
		}
		return NULL;
	}


	/**
	 * Action factory.
	 * @param  string  textual title
	 * @param  string  textual link destination
	 * @param  Html    element which is added to a generated link
	 * @param  bool    use ajax? (add class self::$ajaxClass into generated link)
	 * @param  bool    generate link with argument? (variable $keyName must be defined in data grid)
	 * @return DataGridAction
	 */
	public function addAction($title, $signal, $icon = NULL, $useAjax = FALSE, $type = DataGridAction::WITH_KEY)
	{
		$action = new DataGridAction($title, $signal, $icon, $useAjax, $type);
		$this[] = $action;
		return $action;
	}


	/**
	 * Does column has any action?
	 * @return bool
	 */
	public function hasAction($type = NULL)
	{
		return count($this->getActions($type)) > 0;
	}


	/**
	 * Returns column's action specified by name.
	 * @param  string action's name
	 * @param  bool   throw exception if component doesn't exist?
	 * @return IDataGridColumnAction|NULL
	 */
	public function getAction($name = NULL, $need = TRUE)
	{
		return $this->getNComponent('actions')->getNComponent($name, $need);
	}


	/**
	 * Iterates over all column's actions.
	 * @param  string
	 * @return ArrayIterator|NULL
	 */
	public function getActions($type = 'IDataGridAction')
	{
		$actions = new ArrayNObject();
		foreach ($this->getNComponent('actions')->getNComponents(FALSE, $type) as $action) {
			$actions->append($action);
		}
		return $actions->getIterator();
	}


	/**
	 * NFormats cell's content.
	 * @param  mixed
	 * @param  DibiRow|array
	 * @return string
	 * @throws InvalidStateException
	 */
	public function formatContent($value, $data = NULL)
	{
		throw new InvalidStateException("ActionColumn cannot be formated.");
	}


	/**
	 * Filters data source.
	 * @param  mixed
	 * @throws InvalidStateException
	 * @return void
	 */
	public function applyFilter($value)
	{
		throw new InvalidStateException("ActionColumn cannot be filtered.");
	}



	/********************* interface \ArrayAccess *********************/



	/**
	 * Adds the component to the container.
	 * @param  string  component name
	 * @param  INComponent
	 * @return void.
	 */
	final public function offsetSet($name, $component)
	{
		if (!$component instanceof INComponent) {
			throw new InvalidArgumentException("ActionColumn accepts only INComponent objects.");
		}
		$this->getNComponent('actions')->addNComponent($component, $name == NULL ? count($this->getActions()) : $name);
	}


	/**
	 * Returns component specified by name. Throws exception if component doesn't exist.
	 * @param  string  component name
	 * @return INComponent
	 * @throws InvalidArgumentException
	 */
	final public function offsetGet($name)
	{
		return $this->getAction((string) $name, TRUE);
	}


	/**
	 * Does component specified by name exists?
	 * @param  string  component name
	 * @return bool
	 */
	final public function offsetExists($name)
	{
		return $this->getAction($name, FALSE) !== NULL;
	}


	/**
	 * Removes component from the container. Throws exception if component doesn't exist.
	 * @param  string  component name
	 * @return void
	 */
	final public function offsetUnset($name)
	{
		$component = $this->getAction($name, FALSE);
		if ($component !== NULL) {
			$this->getNComponent('actions')->removeNComponent($component);
		}
	}
}