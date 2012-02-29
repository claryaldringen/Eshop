<?php

class ProductPresenter extends BasePresenter{
	
	private $filter = array();
	private $sort = 'P.id';
	/** @persistent */
	public $id = 0;
	
  public static function getPersistentParams()
  {
  	return array('id');
  }	
	
	public function createComponentCatNForm()
	{
		$model = $model = $this->getInstanceOf('KategorieModel');
		$cats = $model->getAllCats($this->lang);
		$cats[0] = 'Výchozí';
		ksort($cats);
		$cats['all'] = 'Vše';
		$form = new NAppForm($this,'catNForm');
		$form->addSelect('cat','Kategorie:',$cats)
			->getControlPrototype()->onChange('submit()');
		$form->onSuccess[] = array($this,'catNFormSubmited');
		return $form;
	}
	
	public function catNFormSubmited(NAppForm $form)
	{
		$this->id = $form['cat']->getValue();
		$this->redirect('this');	
	}
	
	public function createComponentAdditionalForm()
	{
		$form = new NAppForm($this,'additionalForm');
		$form->addText('nazev','Název vlastnosti:')->addRule(NForm::FILLED,'Musíte zadat název vlastnosti.');
		$form->addText('druhypad','Vlastnost ve 2. pádě:')->addRule(NForm::FILLED,'Musíte zadat název vlastnosti ve 2. pádě.');
		$form->addSubmit('ok','Vytvořit');
		$form->onSuccess[] = array($this,'additionalFormSubmited');
		return $form;
	}
	
	public function additionalFormSubmited(NAppForm $form)
	{
		$model = $this->getInstanceOf('ProductModel');
		$model->setAdditional($form->getValues());
		$this->redirect('this');
	}
	
	public function renderDefault($sort = 'P.id ASC')
	{
		if(!$this->isAjax())$this->sort = $sort;
		$model = $this->getInstanceOf('ProductModel');
		$this->template->celkem = array(0,0);
		$this->template->products = $model->getSklad($this->id,$this->lang,$this->filter,$this->sort);
		$this->template->additionals = $model->getAdditionals2();
	}	
	
	public function handleRename($name,$value)
	{
		$model2 = $this->getInstanceOf('ProductModel');
		$obj = explode('-',$name);
		if($obj[0] == 'i' && $value)$model2->setName($value,$obj[1],$this->lang);
		echo json_encode(array('status'=>0));
		die;
	}
	
	public function handleSetVariant($pid)
	{
		$data = $_POST;
		if(isset($data['jmeno']))$data['jmeno_'.$this->lang] = $data['jmeno'];
		if(isset($data['kus']))$data['kus_'.$this->lang] = $data['kus'];
		unset($data['jmeno']);
		unset($data['kus']);
		$model = $this->getInstanceOf('ProductModel');
		$model->setVariant($pid,$data);
		die(json_encode(array('status'=>0)));
	}
	
	public function handleSetDph($pid,$dph)
	{
		$model = $this->getInstanceOf('ProductModel');
		$model->setDph($pid,$dph);
		die(json_encode(array('status'=>0)));
	}
	
	public function handleSetAdditional($pid)
	{
		$model = $this->getInstanceOf('ProductModel');
		$model->setAdditionalVal($_POST,$pid);
		die(json_encode(array('status'=>0)));
	}
	
	public function handleFilter($name)
	{
		$this->filter[$name] = '%'.$_POST['val'].'%';
		$this->invalidateControl('table');
	}
	
	public function handleSort($sort)
	{
		$this->sort = $sort;
		if($this->isAjax())$this->invalidateControl('table');
		else $this->redirect('this',array('sort'=>$sort));
	}
}
