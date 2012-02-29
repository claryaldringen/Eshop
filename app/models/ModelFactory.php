<?php
/**
 * Tovarni trida pro tvorbu modelu
 *
 * @author Martin Zadrazil
 */
class ModelFactory extends NObject 
{
	private static $instance;
	
	private $models;
	
	private $context;
	
	
	private final function __construct($context)
	{
		$this->context = $context;
	}
	
	/**
	 * Tovarni metoda pro vytvoreni singletonu
	 * 
	 * @param NDiContainer $context
	 * 
	 * @return ModelFactory
	 */
	public static function getInstance($context)
	{
		if(!isset(self::$instance))
		{
			$class = __CLASS__;
			self::$instance = new $class($context);
		}
		return self::$instance;
	}
	
	public function getInstanceOf($model)
	{
		if(!isset($this->models[$model]))
		{
			$this->models[$model] = new $model($this->context);
		}
		return $this->models[$model];
	}
}
