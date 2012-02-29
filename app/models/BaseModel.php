<?php 
abstract class BaseModel extends NObject{
		
	protected $context;
	
	public function __construct(NDiContainer $context)
	{
		require_once LIB_DIR . '/dibi/dibi.php';
		
		$this->context = $context;
		if($_SERVER['HTTP_HOST'] == 'localhost')$config = $this->context->params->dblocal;
		else $config = $config = $this->context->params->database;
		if(!dibi::isConnected())dibi::connect($config);
	}
	
	protected function getInstanceOf($model)
	{
		$mf = ModelFactory::getInstance($this->context);
		return $mf->getInstanceOf($model);
	}
}