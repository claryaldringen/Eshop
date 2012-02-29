<?php

class PaymentPresenter extends BasePresenter{

	public function createComponentPaymentNForm()
	{
		$model = $this->getInstanceOf('KategorieModel');
		$model2 = $this->getInstanceOf('UserModel');
		$form = new NAppForm($this,'paymentNForm');
		$form->addHidden('id');
		$form->addText('jmeno','Interní název:')->addRule(NForm::FILLED,'Musíte vyplnit interní název!');
		$form->addText('jmeno_'.$this->lang,'Název:');
		$form->addText('cena_do','Limit:')
			->addCondition(NForm::FILLED)
			->addRule(NForm::INTEGER,'Limit platební metody musí být číslo!');
		$this->template->countries = $model2->getCountries(TRUE);
		foreach($this->template->countries as $key=>$val)
		{
			$form->addCheckbox($key.'_check',$val);
			$form->addText($key.'_ncena','');	
			$form->addText($key.'_cena','');	
		}
		$form->addSubmit('save','Uložit');
		$form->onSuccess[] = array($this,'paymentNFormSubmited');
		return $form;
	}
	
	public function createComponentDodaniNForm()
	{
		$model = $this->getInstanceOf('KategorieModel');
		$model2 = $this->getInstanceOf('UserModel');
		$form = new NAppForm($this,'dodaniNForm');
		$form->addHidden('id');
		$form->addText('jmeno','Název:');
		$form->addText('cena','Cena:');
		$form->addText('zdarma_od','Zdarma od:')->addRule(NForm::FLOAT,'Zdarma od musí být číslo!');
		$form->addSelect('stav','Stav po vyřízení:',array('pripraveno'=>'Připraveno','odeslano'=>'Odesláno'));
		$form->addTextArea('popis_'.$this->lang,'Popis');
		$this->template->countries = $model2->getCountries(TRUE);
		foreach($this->template->countries as $key=>$val)
		{
			$form->addCheckbox($key.'_check',$val);
			$form->addText($key.'_1_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_1_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_2_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_2_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_3_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_3_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_5_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_5_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_7_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_7_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_10_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_10_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_12_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_12_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_15_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_15_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_20_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_20_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_25_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_25_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_30_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_30_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_35_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_35_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_40_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_40_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
			$form->addText($key.'_50_ncena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Nákupní cena musí být celé  číslo!');	
			$form->addText($key.'_50_cena','')->addCondition(NForm::FILLED)->addRule(NForm::INTEGER,'Cena musí být celé  číslo!');	
		}
		$form->addSubmit('save','Uložit');
		$form->onSuccess[] = array($this,'dodaniNFormSubmited');
		return $form;
	}
	
	public function dodaniNFormSubmited(NAppForm $form)
	{
		
		$model = $this->getInstanceOf('PaymentModel');
		$model->setDodani($form->getValues(),$this->lang);
		$this->redirect('this');	
	}
	
	public function paymentNFormSubmited(NAppForm $form)
	{
		$model = $this->getInstanceOf('PaymentModel');
		$model->setPayment($form->getValues(),$this->lang);
		$this->redirect('this');	
	}
	
	public function createComponentEmailNForm()
	{
		$form = new NAppForm($this,'emailNForm');
		$form->addHidden('typ');
		$form->addHidden('id');
		$form->addText('emailsub','Předmět:',112);
		$form->addTextArea('email','');
		$form->onSuccess[] = array($this,'emailNFormSubmited');
		return $form;
	}
	
	public function emailNFormSubmited(NAppForm $form)
	{
		$values = $form->getValues();
		$values['email_'.$this->lang] = $values['email'];
		$values['emailsub_'.$this->lang] = $values['emailsub'];
		unset($values['email']);
		unset($values['emailsub']);
		$model = $this->getInstanceOf('PaymentModel');
		$model->setEmail($values);
		$this->redirect('this');
	}
	
	public function actionDefault()
	{
		$model = $this->getInstanceOf('PaymentModel');
		$this->template->payments = $model->getAllPayments($this->lang);
		$this->template->dodani = $model->getAllDodani($this->lang,203,false);
		$this->template->payments2 = $model->getPayments2();
	}
	
	public function handleShowNewPayment($id = 0)
	{
		$this->template->showPaymentDialog = true;
		if($id)
		{
			$model = $this->getInstanceOf('PaymentModel');
			$form = $this->getComponent('paymentNForm');
			$form->setDefaults($model->getPayment($id));
			$this->template->id = $id;
		}
		$model2 = $this->getInstanceOf('UserModel');
		$this->template->countries = $model2->getCountries(TRUE);
		$this->invalidateControl('paymentDialog');
	}
	
	public function	handleSetPayment($platba,$dodani)
	{
		$model = $this->getInstanceOf('PaymentModel');
		$model->setPaymentType($platba,$dodani);
		$this->template->payments2 = $model->getPayments2();
		if($this->isAjax())$this->invalidateControl('tabulka');	
		else $this->redirect('this');
	}
	
	public function handleShowNewDodani($id = 0)
	{
		$this->template->showDodaniDialog = true;
		if($id)
		{
			$model = $this->getInstanceOf('PaymentModel');
			$form = $this->getComponent('dodaniNForm');
			$form->setDefaults($model->getDodani($id,$this->lang));
			$this->template->id = $id;
		}
		$model2 = $this->getInstanceOf('UserModel');
		$this->template->countries = $model2->getCountries(TRUE);
		$this->invalidateControl('dodaniDialog');
	}
	
	public function handleShowNewEmail($id, $type)
	{
		$this->template->showEmailDialog = true;
		$model = $this->getInstanceOf('PaymentModel');
		$form = $this->getComponent('emailNForm');
		$form->setDefaults($model->getEmail($id,$type,$this->lang));
		$this->invalidateControl('emailDialog');
	}
	
	public function handleDelete($id,$typ)
	{
		$model = $this->getInstanceOf('PaymentModel');
		if($typ == 1)$model->deletePlatba($id);
		if($typ == 2)$model->deleteDodani($id);
		$this->redirect('this');
	}
}
