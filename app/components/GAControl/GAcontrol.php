<?php

class GAcontrol extends NControl{

	private $orderId = 0;

	public function setOrder($orderId)
	{
		$this->orderId = $orderId;
	}

	public function render()
	{
		$model = new GAModel();

		$template = $this->createTemplate();
		if($this->orderId)$template->order = $model->getOrder($this->orderId);
		$template->gakey = $this->presenter->context->params['google']['gakey'];
		$template->setFile(dirname(__FILE__).'/gacontrol.phtml');
		$template->render();
	}
}
