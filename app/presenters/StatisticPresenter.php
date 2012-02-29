<?php

class StatisticPresenter extends BasePresenter{

	public function createComponentDatumForm()
	{
		$model = new OrdersModel();
		$months = array('všechny','leden','únor','březen','duben','květen','červen','červenec','srpen','září','říjen','listopad','prosinec');
		$form = new NAppForm($this,'datumForm');
		$form->addSelect('month','',$months)
			->getControlPrototype()->onChange('submit()');
		$form->addSelect('year','',$model->getYears())
			->getControlPrototype()->onChange('submit()');
		if(isset($this->params['month']))$form['month']->setDefaultValue($this->params['month']);
		if(isset($this->params['year']))$form['year']->setDefaultValue($this->params['year']);
		$form->onSuccess[] = array($this,'datumFormSubmited');
		return $form;
	}
	
	public function datumFormSubmited(NAppForm $form)
	{
		$this->redirect('this',$form->getValues());	
	}
	
	public function renderDefault($month=0,$year=0)
	{
		
	}
}