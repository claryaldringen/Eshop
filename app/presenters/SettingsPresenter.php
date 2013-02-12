<?php

class SettingsPresenter extends BasePresenter{

	public function createComponentCurrencyForm()
	{
		$mena = array();

		$cnb = new Cnb();
		$codes = $cnb->getAllCode();
		foreach($codes as $code)
			$mena[$code] = $code.' ('.$cnb->getMoney($code,'symbol').')';
		$form = new NAppForm($this,'currencyForm');
		$form->addSelect('mena','Výchozí měna obchodu:',$mena);
		return $form;
	}

	public function createComponentCatTextForm()
	{
		$model = $this->getInstanceOf('SettingsModel');

		$form = new NAppForm($this,'catTextForm');
		$form->addTextArea('cattext','')->setDefaultValue($model->getText($this->lang));
		$form->onSuccess[] = array($this,'catTextFormSubmited');
		return $form;
	}

	public function catTextFormSubmited(NAppForm $form)
	{
		$model = $this->getInstanceOf('SettingsModel');
		$model->setText($form['cattext']->getValue(),$this->lang);
		$this->redirect('this');
	}

	public function renderDefault()
	{
		$model = $this->getInstanceOf('SettingsModel');
		$model2 = $this->getInstanceOf('ProductModel');
		$this->template->countries = $model->getCountries(1);
		$this->template->items = $model2->getAllProducts($this->lang);
		$this->template->tax = $model->getTax();
		$this->template->langs = $model->getLangs();
	}

	public function handleSetAllTax($tax)
	{
		$model = $this->getInstanceOf('SettingsModel');
		$model->setAllTax($tax);
		$this->invalidateControl('course');
	}

	public function handleSetTax($stat,$tax)
	{
		$model = $this->getInstanceOf('SettingsModel');
		$model->setTax($stat,$tax);
		die;
	}

	public function handleAddRow()
	{
		$model = $this->getInstanceOf('SettingsModel');
		$model->addLangRow();
		$this->invalidateControl('language');
	}

	public function handleSetLang($lid,$typ,$val)
	{
		$model = $this->getInstanceOf('SettingsModel');
		$model->setLang(array($typ=>$val),$lid);
		die(json_encode(array('status' => 0)));
	}

	public function handleSave()
	{
	  $model = $this->getInstanceOf('SettingsModel');
	  $model->parsePost($_POST);
	  $this->redirect('this');
	}

	public function handleShowCountryDialog()
	{
	  $model = $this->getInstanceOf('SettingsModel');
	  $this->template->countries2 = $model->getCountries();
	  $this->template->showCountries = true;
	  $this->invalidateControl('countryDialog');
	}

	public function handleToggle($stat)
	{
	  $model = $this->getInstanceOf('SettingsModel');
	  $model->activateCountry($stat);
	  die(json_encode(array('status' => 0)));
	}

	public function handleShowText()
	{
		$this->template->showTextDialog = true;
		$this->invalidateControl('textDialog');
	}

}
