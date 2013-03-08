<?php
abstract class BaseModel extends NObject{

	protected $context;

	public function __construct(NDiContainer $context)
	{
		$this->context = $context;
		if(!dibi::isConnected())dibi::connect($this->context->params['database']);
	}

	protected function getInstanceOf($model)
	{
		$mf = ModelFactory::getInstance($this->context);
		return $mf->getInstanceOf($model);
	}
}