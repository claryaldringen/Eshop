<?php

class OrdersPresenter extends BasePresenter{
		
	/** @persistent int*/
	public $orderId;
	private $sort = 'O.id DESC';
	private $ajax = false;
	
	private function createComponentNForm($name,$id)
	{
		$pole = array('prijato'=>'Přijato','pripraveno'=>'Připraveno','odeslano'=>'Odesláno','vyrizeno'=>'Vyřízeno');
		$model = $this->getInstanceOf('OrdersModel');
		$stav = $model->getPossStav($id);
		if($stav == 'pripraveno')unset($pole['odeslano']);
		else unset($pole['pripraveno']);
		$form = new NAppForm($this,$name);
		$form->addHidden('id')->setValue($id);
		$form->addSelect('stav','',$pole)
			->getControlPrototype()->onChange('submit()');
		$form->onSuccess[] = array($this,'formSubmited');
		return $form;
	}
	
	public function formSubmited(NAppForm $form)
	{
		$vals = $form->getValues();
		$model = $this->getInstanceOf('OrdersModel');
		$model->setStav($vals['stav'],$vals['id']);
		$this->redirect('this');	
	}
	
	public function createComponentDiffForm()
	{
		$model = $this->getInstanceOf('OrdersModel');
		$items = $model->getItemsInOrder($this->orderId,$this->lang);
		
		$form = new NAppForm($this,'diffForm');
		$form->addHidden('order')->setValue($this->orderId);
		foreach($items as $item)
		{
			$form->addCheckbox('check_'.$item->id,$item->count.' '.$item->kus.' '.$item->jmeno.' '.$item->varname);
		}
		$form->addSubmit('ok','Vytvořit novou objednávku z vybraných položek');
		$form->onSuccess[] = array($this,'diffFormSubmited');
		return $form;	
	}
	
	public function diffFormSubmited(NAppForm $form)
	{
		$model = $this->getInstanceOf('OrdersModel');
		$model->createOrder($form->getValues());
		$this->orderId = 0;
		$this->redirect('this');	
	}
	
	public function createComponentStavNForm()
	{
		$model = $this->getInstanceOf('OrdersModel');
		$months = array('všechny','leden','únor','březen','duben','květen','červen','červenec','srpen','září','říjen','listopad','prosinec');
		$form = new NAppForm($this,'stavNForm');
		$form->addSelect('stav','Stav:',array('prijato'=>'Přijato','pripraveno'=>'Připraveno','odeslano'=>'Odesláno','vyrizeno'=>'Vyřízeno'))
			->getControlPrototype()->onChange('submit()');
		$form->addSelect('month','',$months)
			->getControlPrototype()->onChange('submit()');
		$form->addSelect('year','',$model->getYears())
			->getControlPrototype()->onChange('submit()');
		if(isset($this->params['stav']))$form['stav']->setDefaultValue($this->params['stav']);
		if(isset($this->params['month']))$form['month']->setDefaultValue($this->params['month']);
		if(isset($this->params['year']))$form['year']->setDefaultValue($this->params['year']);
		$form->onSuccess[] = array($this,'stavNFormSubmited');
		return $form;
	}
	
	public function stavNFormSubmited(NAppForm $form)
	{
		$this->redirect('this',(array)$form->getValues());	
	}
	
	public function actionDefault($stav = 'prijato',$month=0,$year=0)
	{
		if(!$this->isAjax() || $this->ajax)
		{
			if($year == 0)$year = date("Y");
		  $model = $this->getInstanceOf('OrdersModel');
		  // Pricteni ceny za postovne do nakladu
		  $model->setPaymentPrice();
		  $this->template->czisk = array(0,0,0);
		  $this->template->orders = $model->getOrders($this->lang,$stav,$month,$year,$this->sort);
		  $this->template->stavNForm = array();
		  foreach($this->template->orders AS $order)
		  {
			  $this->template->stavNForm[$order->id] = $this->createComponentNForm('test'.$order->id,$order->id);
			  $this->template->stavNForm[$order->id]['stav']->setDefaultValue($order->stav);
		  }
		}
	}
	
	public function renderSpecial($id)
	{
		$model = $this->getInstanceOf('ProductModel');
		$this->template->specials = $model->getSpecialFromOrder($id, $this->lang);
	}
	
	public function actionCheque($id)
	{
		$model = $this->getInstanceOf('OrdersModel');
		$this->template->order = $model->getOrder($id);
		$this->template->owner = $this->context->params->owner;
	}
	
	public function handleStorno($id)
	{
		$model = $this->getInstanceOf('OrdersModel');
		$model->storno($id);
		$this->redirect('this');
	}
	
	public function handleShowItem($itemid)
	{
	  $model = $this->getInstanceOf('ProductModel');
	  $res = $model->getImages($itemid, $this->lang);
	  $src = '';
	  foreach($res as $key=>$val)
	  {
	    $src = $this->template->baseUri.'/images/uploaded/large'.$key.'.jpg';
	    break;
	  }
	  die(json_encode(array('src'=>$src)));
	}
	
	public function handleFaktura($id)
	{
		$model = $this->getInstanceOf('OrdersModel');
		$model->getInvoice($id,$this->lang);
	}
	
	public function handleMerge($id,$id2)
	{
		$model = $this->getInstanceOf('OrdersModel');
		try{
			$model->mergeOrders($id,$id2);
		}catch(InvalidArgumentException $e){
			$this->flashMessage('Tyto objednávky nemohou být sloučeny.');
			throw $e;
		}
		$this->redirect('this');	
	}
	
	public function handleShowDialog($id)
	{
		$this->orderId = $id;
		$this->template->showDialog = true;
		$this->invalidateControl('dialog');
	}
	
	public function handleSetSleva($id,$id2)
	{
		$model = $this->getInstanceOf('OrdersModel');
		$model->setSleva($id,$id2);
		$this->redirect('this');
	}

	public function handleSetCount($id,$id2)
	{
		$model = $this->getInstanceOf('OrdersModel');
		$model->setCount($id,$id2);
		$this->redirect('this');
	}
	
	public function handleDelete($id)
	{
		$model = $this->getInstanceOf('OrdersModel');
		$model->deleteItem($id);
		$this->redirect('this');
	}
	
	public function handleSort($sort)
	{
		$this->sort = $sort;
		$this->ajax = true;
		if($this->isAjax())$this->invalidateControl('table');
		else $this->redirect('this',array('sort'=>$sort));
	}
	
	public function handleSetPrice($id,$id2)
	{
		$model = $this->getInstanceOf('OrdersModel');
		$model->setPrice($id,$id2);
		$this->redirect('this');	
	}
}
