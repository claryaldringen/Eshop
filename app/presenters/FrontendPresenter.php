<?php

class FrontendPresenter extends BasePresenter
{

	const addressEmptyValue = "Společnost\r\nJméno a příjmení\r\nUlice a čp.\r\nPSČ - Město";

	/** @persistent int */
	public $mode;
	public $product;
	public $mena;
	public $stat;

	public function startup()
	{
		parent::startup();

		$httpResponse = NEnvironment::getHttpResponse();
		$httpResponse->setCookie('mercas','obsahkukinstejnenikdonecte',time()+24*3600);	// Ochrana proti spamu

		$user = NEnvironment::getUser();
		if($user->isLoggedIn())
		{
			$this->mena = $user->getIdentity()->data['mena'];
			$this->stat = $user->getIdentity()->data['stat'];
		}else{
			$httpRequest = NEnvironment::getHttpRequest();
			$this->mena = $httpRequest->getCookie('mercurr');
		}
		if(!$this->mena || !$this->stat)
		{
			$config = $this->context->params['local'];
			$domain = explode('.',$_SERVER['HTTP_HOST']);
			$dindex = count($domain)-1;
			$local = explode(';',$config[$domain[$dindex]]);
			if(count($local) == 3)
			{
				if(!$this->lang)$this->lang = $local[0];
				if(!$this->mena)$this->mena = $local[1];
				if(!$this->stat)$this->stat = $local[2];
			}
		}
		$this->template->mena = $this->mena;
	}

	protected function createComponentRegistrationNForm(){

		$cnb = $this['cnb'];
		$meny = $cnb->getAllCode();
		$meny = array_combine($meny,$meny);
		$model = $this->getInstanceOf('UserModel');

		$form = new NAppForm($this, 'registrationNForm');
		$form->addHidden('id');
		$form->addText('jmeno', '*Jméno:')->addRule(NForm::FILLED,'Vyplňte jméno!');
		$form->addText('prijmeni', '*Příjmení:')->addRule(NForm::FILLED,'Vyplňte příjmení!');
		$form->addText('email', '*E-mail:')
			->addRule(NForm::FILLED,'Vyplňte e-mail!')
			->addRule(NForm::EMAIL,'E-mail nemá správný tvar!');
		$form->addText('ulice', '*Ulice a čp.:')->addRule(NForm::FILLED,'Vyplňte ulici a číslo popisné!');
		$form->addText('mesto', '*Město:')->addRule(NForm::FILLED,'Vyplňte město!');
		$form->addText('psc', '*PSČ:')->addRule(NForm::FILLED,'Vyplňte PSČ!');
		$form->addSelect('stat','Stát:',$model->getCountries())->setDefaultValue($this->stat);
		$form->addSelect('mena','Preferovaná měna:',$meny)->setDefaultValue($this->mena);
		$form->addText('telefon', 'Telefon:');
		$form->addText('firma', 'Firma:');
		$form->addText('ico', 'IČO:');
		$form->addText('dic', 'DIČ:');
		$form->addText('login', '*Uživatelské jméno:')
			->addRule(NForm::FILLED,'Vyplňte login!');

		$form->addPassword('heslo', '*Heslo:')->getControlPrototype()->autocomplete('off');
		$form->addPassword('heslo2', 'Heslo znovu:')
			->addConditionOn($form['heslo'],NForm::FILLED)
			->addRule(NForm::EQUAL, 'Hesla se musí shodovat!', $form['heslo']);
		$form->addCheckbox('news','Zasílat novinky e-mailem')->setDefaultValue(TRUE);

		$form->onSuccess[]= array($this, 'processRegistration');

		if($this->user->isInRole('1'))
		{
			$form->setDefaults($model->getUser($this->userdata['id']));
			$form->addSubmit('register', 'Uložit');
		}else{
			$form->addSubmit('register', 'Registrovat se');
			$form['login']->addRule(~NForm::IS_IN,'Toto uživatelské jméno je již zabrané!',$model->getLogins());
			$form['heslo']->addRule(NForm::FILLED, 'Vyplňte heslo!');
		}

		return $form;
	}

	public function processRegistration(NAppForm $form)
	{
		$model = $this->getInstanceOf('UserModel');
		$values = $form->getValues();
		unset($values['heslo2']);
		if($values['id'])
		{
			$model->updateUser($values);
			$this->flashMessage('Vaše údaje byly změněny.');
			$this->redirect('this');
		}

		$stat = $model->setUser($values,$this);
		if($stat == 1)$this->redirect('order');
		elseif($stat == 2)$this->flashMessage('Vaše registrace byla úspěšně dokončena, nyní se můžete přihlásit.');
		else $this->flashMessage('Na vaši e-mailovou adresu \''.$values['email'].'\' byl zaslán e-mail s dokončením registrace.');
		$this->redirect('default');
	}

	public function createComponentSortForm()
	{
		$sort = array('P.sort'=>'ničeho',
			'V.cena,P.cena'=>'ceny vzestupně',
			'V.cena DESC,P.cena DESC'=>'ceny sestupně',
			'jmeno'=>'abecedy vzestupně',
			'jmeno DESC'=>'abecedy sestupně'
		);
		$session = NEnvironment::getSession('shop');
		$form = new NAppForm($this,'sortForm');
		$form->addSelect('sort','Řadit dle:',$sort)->getControlPrototype()->onChange('submit()');
		$form['sort']->setDefaultValue($session->sort);
		$form->onSuccess[] = array($this,'sortFormSubmited');
		return $form;
	}

	public function sortFormSubmited(NAppForm $form)
	{
		$session = NEnvironment::getSession('shop');
		$session->sort = $form['sort']->getValue();
		$this->redirect('this');
	}

 	public function createComponentHodnoceni()
 	{
 	  $hodnoceni = new RatingControl($this,'hodnoceni',$this->userdata['id'],$this->product->id);
 	  return $hodnoceni;
 	}

 	public function createComponentDiscussion()
	{
		$session = NEnvironment::getSession('shop');
		$session->actualItem = $this->product->id;
	  $discussion = new Discussion($this,'discussion',$this->lang);
		return $discussion;
	}

	public function createComponentTree()
	{
	  $tree = new Tree($this,'tree', $this->lang);
	  return $tree;
	}

	public function createComponentForm($name)
	{
		$form = new NAppForm($this,$name);
		$form->addHidden('id');
		$form->addText('pocet','x',1)
			->setDefaultValue(1)
			->addRule(NForm::INTEGER,'Počet položek musí být celé číslo!')
			->addRule(NForm::RANGE,'Počet položek musí být větší než 0!',array(0,10000000));
		$form->addSubmit('tobasket','');
		$form['pocet']->getControlPrototype()->class('pocet');
		$form['tobasket']->getControlPrototype()->class('koupit');
		$form->onSuccess[] = array($this,'addToBasket');
		return $form;
	}

	public function addToBasket(NAppForm $form)
	{
		$model1 = $this->getInstanceOf('UserModel');
		$model2 = $this->getInstanceOf('ProductModel');
		if(!$this->user->isLoggedIn())
		{
			$pole = $model1->setGuest();
			$this->user->login($pole['username'],$pole['password']);
		}
		$model2->addToBasket($form['pocet']->getValue(),$form['id']->getValue(),$this->user->getIdentity()->data['id']);
		$this->redirect('this');
	}

	public function createComponentDetailNForm()
	{
		$form = new NAppForm($this,'detailNForm');
		$form->onSuccess[] = array($this,'detailNFormSubmited');
		return $form;
	}

	public function createComponentInBasketNForm()
	{
		$form = new NAppForm($this,'inBasketNForm');
		$form->addSubmit('save','Uložit změny')->onClick[] = array($this,'saveClicked');
		$form->addSubmit('delete','Vymazat košík')->onClick[] = array($this,'deleteClicked');
		$form->addSubmit('toorder','K objednávce')->onClick[] = array($this,'toorderClicked');
		$form['save']->getControlPrototype()->class('kosikbutt');
		$form['delete']->getControlPrototype()->class('kosikbutt');
		$form['toorder']->getControlPrototype()->class('kosikbutt');
		return $form;
	}

	public function saveClicked(NSubmitButton $button)
	{
		$model = $this->getInstanceOf('ProductModel');
		$form = $button->getForm();
		$model->setBasket($this->user->getIdentity()->data['id'],$form->getValues());
		$this->redirect('this');
	}

	public function deleteClicked(NSubmitButton $button)
	{
		$model = $this->getInstanceOf('ProductModel');
		$model->deleteBasket($this->user->getIdentity()->data['id']);
		$this->redirect('this');
	}

	public function toorderClicked(NSubmitButton $button)
	{
		$model = $this->getInstanceOf('ProductModel');
		$form = $button->getForm();
		$model->setBasket($this->user->getIdentity()->data['id'],$form->getValues());
		if($this->user->isInRole('3'))
		{
			$session = NEnvironment::getSession('shop');
			$session->toorder = true;
			$this->redirect('registration');
		}
		else $this->redirect('order');
	}

	public function createComponentForgottenPasswordForm()
	{
	  $form = new NAppForm($this,'forgottenPasswordForm');
	  $form->addText('nickoremail','Zadejte vaše přihlašovací jméno nebo email:')->addRule(NForm::FILLED,'Zadejte vaše přihlašovací jméno nebo email');
	  $form['nickoremail']->getControlPrototype()->class('vypln');
	  $form->addSubmit('ok','OK')->getControlPrototype()->class('kosikbutt');
	  $form->onSuccess[] = array($this,'forgottenPasswordFormSubmited');
	  return $form;
	}

	public function forgottenPasswordFormSubmited(NAppForm $form)
	{
	  $model = $this->getInstanceOf('UserModel');
	  $val = $form['nickoremail']->getValue();
	  if($model->sendForgottenPassword($val,$this) == true)$this->redirect('passsend');
	  else{
	    $this->flashMessage("Uživatelské jméno nebo email '$val' nebyl nalezen, zkontrolujte prosím překlepy.");
	    $this->redirect('this');
	  }
	}

	public function createComponentDodaniNForm()
	{
		$model = $this->getInstanceOf('PaymentModel');
		$productModel = $this->getInstanceOf('ProductModel');
		$userModel = $this->getInstanceOf('UserModel');

		$session = NEnvironment::getSession('shop');
		$cena = $productModel->getBasket($this->user->getIdentity()->data['id']);
		$poleDodani = $model->getAllDodani($this->lang,$this->stat);
		if(isset($session->dodani))$aktualniDodani = $session->dodani;
		else $aktualniDodani = key($poleDodani);

		$platby = $model->getPayments($aktualniDodani,$cena['cena'],$this->lang,$this->stat);
		if(isset($session->platba) && in_array($session->platba, array_keys($platby)))$aktualniPlatba = $session->platba;
		else $aktualniPlatba = key($platby);

		$form = new NAppForm($this,'dodaniNForm');
		$form->addSelect('dadresa','',$userModel->getAddresses());
		$form->addTextArea('newdadresa','',15,2)->setEmptyValue(self::addressEmptyValue);
		$form->addSelect('fadresa','',$userModel->getAddresses());
		$form->addTextArea('newfadresa','',15,2)->setEmptyValue(self::addressEmptyValue);
		$form->addTextArea('pozn','Poznámka:',30,5);

		$form->addSelect('dodani','*Způsob dodání:',$poleDodani)
			->getControlPrototype()->onChange("$('#".$form->getElementPrototype()->id."').ajaxSubmit();");

		$form->addSelect('platba','Způsob platby:',$platby)
			->getControlPrototype()->onChange("$('#".$form->getElementPrototype()->id."').ajaxSubmit();");

		$form->addSubmit('order','Objednat')->getControlPrototype()->class('kosikbutt');

		$form['dodani']->setDefaultValue($aktualniDodani);
		$form['platba']->setDefaultValue($aktualniPlatba);

		$form->onSuccess[] = array($this,'dodaniNFormSubmited');
	}

	public function dodaniNFormSubmited(NAppForm $form)
	{
		$model2 = $this->getInstanceOf('ProductModel');
		if($this->isAjax())
		{
			$model = $this->getInstanceOf('PaymentModel');
			$cena = $model2->getBasket($this->user->getIdentity()->data['id']);
			$weight = $model2->getWeight($this->user->getIdentity()->data['id']);
			$form = $this->getComponent('dodaniNForm');
			if($form['dodani']->getValue())
			{
				$session = NEnvironment::getSession('shop');
				$session->dodani = $form['dodani']->getValue();
				$platby = $model->getPayments($form['dodani']->getValue(),$cena['cena'],$this->lang,$this->stat);
				$dodani = $model->getDodani($form['dodani']->getValue(),$this->lang,ProductModel::getWeight($this->userdata['id']),$this->stat);

				if(isset($_POST['platba']))
				{
					$platba = $model->getPayment($_POST['platba'],$this->stat);
					$session->platba = $_POST['platba'];
				}
				else $platba = $model->getPayment(key($platby),$this->stat);

				if($cena['cena'] >= $dodani->zdarma_od)$this->template->balne = 0;
				else $this->template->balne = $dodani->cena;

				if(isset($platba->cena))$this->template->balne += $platba->cena;

				$form['platba']->setItems($platby);
			}
			$this->invalidateControl('dodani');
			$this->invalidateControl('basket');
		}else{
			$model = $this->getInstanceOf('PaymentModel');
			$dodani = $model->getDodani($form['dodani']->getValue(),$this->lang,ProductModel::getWeight($this->userdata['id']),$this->stat);
			$platba = $model->getPayment($_POST['platba'],$this->stat);
			$session = NEnvironment::getSession('shop');
			$values = $_POST;
			unset($values['order']);
			$values['lang'] = $this->lang;
			$values['mena'] = $this->mena;
			$values['platba_cena'] = $platba->cena;
			$values['dodani_cena'] = $dodani->cena;
			$values['platba_ncena'] = $platba->ncena;
			$values['dodani_ncena'] = $dodani->ncena;

			$orderModel = $this->getInstanceOf('OrdersModel');
			if($values['newdadresa'] == self::addressEmptyValue)unset($values['newdadresa']);
			if($values['newfadresa'] == self::addressEmptyValue)unset($values['newfadresa']);
			$session->orderid = $orderModel->setObjednavka($values,$this->user->getIdentity()->data['id'],($platba->cena+$dodani->cena));
			$this->redirect('orderend');
		}
	}

	public function createComponentCurrencyForm()
	{
		$codes = array('CZK','EUR','USD','GBP','PLN');

		$cnb = $this['cnb'];
		$meny = array();
		foreach($codes as $code)
		{
			$money = $cnb->getMoney($code);
			$meny[$code] = $money['symbol'];
		}

		$model = $this->getInstanceOf('PaymentModel');
		$model2 = $this->getInstanceOf('ProductModel');
		$cena = array('cena'=>0);
		if($this->user->isLoggedIn())
		{
			$session = NEnvironment::getSession('shop');
			$cena = $model2->getBasket($this->user->getIdentity()->data['id']);
			$poleDodani = $model->getAllDodani($this->lang,$this->stat);
			if(isset($session->dodani))$aktualniDodani = $session->dodani;
			else $aktualniDodani = key($poleDodani);

			$platby = $model->getPayments($aktualniDodani,$cena['cena'],$this->lang,$this->stat);
			if(isset($session->platba) && in_array($session->platba, array_keys($platby)))$aktualniPlatba = $session->platba;
			else $aktualniPlatba = key($platby);
		}

		$form = new NAppForm($this,'currencyForm');
		$form->addSelect('mena','Měna:',$meny)->setDefaultValue($this->mena);
		$form['mena']->getControlPrototype()->onChange('submit()');
		if($this->user->isLoggedIn())
		{
			$form->addSelect('dodani','Způsob dodání:',$poleDodani)
				->getControlPrototype()->onChange("$('#".$form->getElementPrototype()->id."').ajaxSubmit();");

			$form->addSelect('platba','Způsob platby:',$platby)
				->getControlPrototype()->onChange("$('#".$form->getElementPrototype()->id."').ajaxSubmit();");

			$form['dodani']->setDefaultValue($aktualniDodani);
			$form['platba']->setDefaultValue($aktualniPlatba);
		}
		$form->onSuccess[] = array($this,'currencyFormSubmited');
		return $form;
	}

	public function currencyFormSubmited(NAppForm $form)
	{
		$values = $form->getValues();
		if($this->isAjax())
		{

			$model = $this->getInstanceOf('PaymentModel');
			$model2 = $this->getInstanceOf('ProductModel');
			$cena = $model2->getBasket($this->user->getIdentity()->data['id']);
			$weight = $model2->getWeight($this->user->getIdentity()->data['id']);
			$form = $this->getComponent('currencyForm');
			if($form['dodani']->getValue())
			{
				$session = NEnvironment::getSession('shop');
				$session->dodani = $form['dodani']->getValue();

				$platby = $model->getPayments($form['dodani']->getValue(),$cena['cena'],$this->lang,$this->stat);
				$dodani = $model->getDodani($form['dodani']->getValue(),$this->lang,ProductModel::getWeight($this->userdata['id']),$this->stat);

				if(isset($_POST['platba']) && in_array($_POST['platba'], array_keys($platby)))
				{
					$platba = $model->getPayment($_POST['platba'],$this->stat);
					$session->platba = $_POST['platba'];
				}
				else $platba = $model->getPayment(key($platby),$this->stat);

				if($cena['cena'] >= $dodani->zdarma_od)$this->template->balne = 0;
				else $this->template->balne = $dodani->cena;

				if(isset($platba->cena))$this->template->balne += $platba->cena;
				$form['platba']->setItems($platby);
			}
			if($this->getAction() == 'order')$this->invalidateControl('dodani');
			$this->invalidateControl('basket');
		}else{
			if($this->user->isLoggedIn())
			{
				$model = $this->getInstanceOf('UserModel');
				$this->userdata = $this->user->getIdentity()->getData();
				$model->updateUser(array('id'=>$this->userdata['id'],'mena'=>$values['mena']));
			}else{
				$httpResponse = NEnvironment::getHttpResponse();
				$httpResponse->setCookie('mercurr', $values['mena'], time() + 30 * 24 * 60 * 60);
			}
			$this->redirect('this');
		}
	}

	public function createComponentKontaktForm()
	{
		$form = new NAppForm($this,'kontaktForm');
		$form->addText('email','Váš e-mail:')
			->addRule(NForm::FILLED,'Musíte vyplnit váš e-mail, abychom vám mohli zaslat odpověď.')
			->addRule(NForm::EMAIL,'Zadaný e-mail nemá správný tvar.');
		$form->addTextArea('message','Zpráva:')->addRule(NForm::FILLED,'Musíte vyplnit vzkaz.');
		$form->addSubmit('send','Odeslat')->getControlPrototype()->class('kosikbutt');
		$form->onSuccess[] = array($this,'kontaktFormSubmited');
		return $form;
	}

	public function kontaktFormSubmited(NAppForm $form)
	{
		$model = $this->getInstanceOf('MailModel');
		$model->sendMessage($form->getValues());
		$this->flashMessage('Váš vzkaz byl odeslán, budeme se snažit odpovědět co nejdříve.');
		$this->redirect('this');
	}

	/**
	 * Vytvoreni formulare kam se zadavaji specialni vlastnosti
	 *
	 * @see NComponentContainer::createComponent()
	 */
	public function createComponentNForm($name,$id)
	{
		$model = $this->getInstanceOf('ProductModel');
		$form = new NAppForm($this,$name);
		$form->addHidden('vlastnik')->setValue($id);
		$specials = $model->getSpecialsOnFrontend($id,$this->lang);
		foreach($specials as $id=>$special)
		{
			if($special->typ == 1)$form->addText('special_'.$special->id,$special->name);
			if($special->typ == 2)$form->addTextArea('special_'.$special->id,$special->name);
			if($special->typ == 3)$form->addSelect('special_'.$special->id,$special->name,$special->values);
			if($special->typ == 4)$form->addMultiSelect('special_'.$special->id,$special->name,$special->values,5);
			if($special->typ == 5)$form->addFile('special_'.$special->id,$special->name);
			foreach($special->rules as $rule)
			{
				if($rule->typ == 1)$form['special_'.$special->id]->addRule(NForm::FILLED,"Musíte  vyplnit pole s názvem '".$special->name."'");
				if($rule->typ == 2)$form['special_'.$special->id]->addRule(NForm::FLOAT,"Pole s názvem '".$special->name."' musí být číslo.");
				if($rule->typ == 3)$form['special_'.$special->id]->addRule(NForm::INTEGER,"Pole s názvem '".$special->name."' musí být celé číslo.");
				if($rule->typ == 4)$form['special_'.$special->id]->addRule(NForm::INTEGER,"Pole s názvem '".$special->name."' musí být v rozmezí od %d do %d.",array($rule->range1,$rule->range0));
			}
		}
		$form->addText('pocet','Počet:')
			->setDefaultValue(1)
			->addRule(NForm::INTEGER,'Počet položek musí být celé číslo!')
			->addRule(NForm::RANGE,'Počet položek musí být větší než 0!',array(0,10000000));
		$form->addSubmit('tobasket','')->getControlPrototype()->class('koupit');
		$form['pocet']->getControlPrototype()->class('pocet');
		$form->onSuccess[] = array($this,'specialFormSubmited');
		return $form;
	}

	public function specialFormSubmited(NAppForm $form)
	{
		$model = $this->getInstanceOf('ProductModel');
		$model1 = $this->getInstanceOf('UserModel');
		$values = $form->getValues();
		$pocet = $values['pocet'];
		unset($values['pocet']);

		try{
			$id = $model->createSpecialVariant($values);
			if(!$this->user->isLoggedIn())
			{
				$pole = $model1->setGuest();
				$this->user->login($pole['username'],$pole['password']);
			}
			$model->addToBasket($pocet,$id,$this->user->getIdentity()->data['id']);
			$this->redirect('this');
		}catch(UnexpectedValueException $e){
			$form->addError($e->getMessage());
		}
	}

	public function beforeRender()
	{
		parent::beforeRender();
		$model = $this->getInstanceOf('ProductModel');
		if($this->user->isLoggedIn())$this->template->showprices = TRUE;
		else $this->template->showprices = $this->context->params['frontend']['showprices'];
		$this->template->news = $model->getNewProducts(5,$this->lang);
		$this->template->bests = $model->getBestsellers(5,$this->lang);
		$this->template->recomended = $model->getRecomended(5,$this->lang);
		if($this->user->isLoggedIn())$this->template->basket = $model->getBasket($this->user->getIdentity()->data['id']);
	  if(!isset($this->template->title))$this->template->title = "Repliky artefaktů pro oživenou historii a historický šerm";

		try{
	  	$this->setBalne();
		}catch(InvalidArgumentException $e){
			if($e->getCode() == 666)
			{
				if($this->getAction() != 'kontakt')
				{
					$this->flashMessage('Zdá se, že do Vaší země nejsme momentálně schopni zboží dopravit. Kontaktujte nás prosím emailem nebo formulářem umístěným níže.');
					$model = $this->getInstanceOf('UserModel');
					$model->updateUser(array('id' => $this->userdata['id'], 'stat' => 203));
					$this->redirect('kontakt');
				}
			}else NDebugger::log($e);
		}
	}

	public function actionKategorie($path)
	{
		$session = NEnvironment::getSession('shop');
		$tree = $this->getComponent('tree');
		$tree->setPath($path);

		$model1 = $this->getInstanceOf('KategorieModel');
		$model2 = $this->getInstanceOf('ProductModel');
		$id = $model1->getIdFromPath($path,$this->lang);

		$vp = new VisualPaginator($this, 'vp');
		$paginator = $vp->getPaginator();
    $paginator->itemsPerPage = 15;
    $paginator->itemCount = $model2->getProductCount($id,$this->lang);

    $this->template->path = $path;
		$this->template->mkategorie = $model1->getCategory($id,$this->lang);
		$this->template->title = $this->template->mkategorie->jmeno;
		$products = $model2->getProducts($id,$paginator->offset,$paginator->itemsPerPage,$this->lang,$session->sort);
		foreach($products as $key=>$product)
		{
			if($product->pocetVar == 1){
				$products[$key]->form = $this->createComponentForm('form'.$key);
				$products[$key]->form['id']->setValue($product->varianta);
			}
			$products[$key]->path = $model2->getProductCPath($product->id,$this->lang);
		}
		$this->template->products = $products;
	}

	public function actionDetail($path,$produkt)
	{
		$model = $this->getInstanceOf('ProductModel');
		$id = $model->getProductFromPath($path,$produkt,$this->lang);
		$spec = $model->getSpecials($id,$this->lang);

		$this->product = $model->getProduct($id,$this->lang);
		$this->template->aktualfoto = key($this->product->images);
		$this->template->suplements = $model->getSuplements($id,$this->lang,'supl');
		$this->template->complements = $model->getSuplements($id,$this->lang,'comp');
		if(!empty($spec))$this->template->specialForm = $this->createComponentNForm('specialForm',$id);
	}

	public function actionBasket()
	{
		if($this->user->isLoggedIn())
		{
			$model = $this->getInstanceOf('ProductModel');
			$products = $model->getBasketDetail($this->user->getIdentity()->data['id'],$this->lang);
			$form = $this->getComponent('inBasketNForm');
			foreach($products as $product)
			{
				$form->addText('pocet_'.$product->id,'x',1)
					->setDefaultValue((int)$product->pocet)
					->addRule(NForm::INTEGER,'Počet položek musí být celé číslo!')
					->addRule(NForm::RANGE,'Počet položek musí být větší než 0!',array(0,10000000));
				$form['pocet_'.$product->id]->getControlPrototype()->class('pocet');
			}
		$this->template->basketDetail = $products;
		}else $this->redirect('default');
	}

	public function actionObjednavky()
	{
		$model = $this->getInstanceOf('OrdersModel');
		$this->template->orders = $model->getUserOrders($this->lang,$this->user);
	}

	public function actionSearch()
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		if(!empty($session->search))
		{
		$products = $model->getProductsByIds($session->search,$this->lang);
		foreach($products as $key=>$product)
		{
			if($product->pocetVar == 1){
				$products[$key]->form = $this->createComponentForm('form'.$key);
				$products[$key]->form['id']->setValue($product->varianta);
			}
			$products[$key]->path = $model->getProductCPath($product->id,$this->lang);
		}
		$this->template->products = $products;
		}
	}

	public function renderDefault()
	{
		$model = $this->getInstanceOf('ProductModel');
		$model2 = $this->getInstanceOf('SettingsModel');

		$products = $model->getProductsInSleva($this->lang);
		foreach($products as $key=>$product)
		{
			if($product->pocetVar == 1){
				$products[$key]->form = $this->createComponentForm('form'.$key);
				$products[$key]->form['id']->setValue($product->varianta);
			}
			$products[$key]->path = $model->getProductCPath($product->id,$this->lang);
		}
		$this->template->products = $products;
		$this->template->text = $model2->getText($this->lang);

		$settingsModel = $this->getInstanceOf('SettingsModel');
		$this->template->pagetext = $settingsModel->getPageText(1, $settingsModel->getLangId($this->lang))->content;
	}

	public function renderDetail($path, $produkt)
	{
		$tree = $this->getComponent('tree');
		$tree->setPath($path);

		$this->template->product = $this->product;
		$title = '';
		foreach($this->product->properties as $key=>$val)
		{
			$title = $val;
			break;
		}
		$this->template->title = $this->product->jmeno.', '.$title;
		$form = $this->getComponent('detailNForm');
		foreach($this->product->variants as $var)
		{
			$form->addText('var_'.$var->id,'x',1)
				->setDefaultValue(1)
				->addRule(NForm::INTEGER,'Počet položek musí být celé číslo!')
				->addRule(NForm::RANGE,'Počet položek musí být větší než 0!',array(0,10000000));
			$form->addSubmit('sub_'.$var->id,'');
			$form['var_'.$var->id]->getControlPrototype()->class('pocet');
			$form['sub_'.$var->id]->getControlPrototype()->class('koupit');
		}
	}

	public function actionOrder()
	{
		if(!$this->user->isLoggedIn())
		{
			$this->flashMessage('Pro tuto akci musíte být přihlášeni.');
			$this->redirect('default');
		}
		$model = $this->getInstanceOf('PaymentModel');
		$model2 = $this->getInstanceOf('ProductModel');
		$cena = $model2->getBasket($this->user->getIdentity()->data['id']);
		$poleDodani = $model->getAllDodani($this->lang,$this->stat);
		$dodani = $dodani = $model->getDodani(key($poleDodani),$this->lang,ProductModel::getWeight($this->userdata['id']),$this->stat);
		$platby = $model->getPayments(key($poleDodani),$cena['cena'],$this->lang,$this->stat);
		$platba = $model->getPayment(key($platby),$this->stat);

		if($cena['cena'] >= $dodani->zdarma_od)$this->template->balne = 0;
		else $this->template->balne = $dodani->cena;

		if(isset($platba->cena))$this->template->balne += $platba->cena;
	}

	public function renderOrder()
	{
		$model = $this->getInstanceOf('ProductModel');
		$products = $model->getBasketDetail($this->user->getIdentity()->data['id'],$this->lang);
		if(empty($products))
		{
			$this->flashMessage('Košík je prázdný');
			$this->redirect('default');
		}
		$this->template->basketDetail = $products;
		$this->template->userdata = $this->user->getIdentity()->getData();
	}

	public function renderOrderend()
	{
		$session = NEnvironment::getSession('shop');
		if($this->userdata['id'] != 3)	//Aby to do statistiky nepočítalo uživatele Martin
		{
			$this->getComponent('ga')->setOrder($session->orderid);
		}
		$this->template->orderid = $session->orderid;
	}

	public function renderKontakt()
	{
		$httpRequest = NEnvironment::getHttpRequest();
		if($httpRequest->getCookie('mercas') === 'obsahkukinstejnenikdonecte')$this->template->showSubmit = true;
		$settingsModel = $this->getInstanceOf('SettingsModel');
		$this->template->pagetext = $settingsModel->getPageText(2, $settingsModel->getLangId($this->lang))->content;
	}


	public function renderPodminky()
	{
		$settingsModel = $this->getInstanceOf('SettingsModel');
		$this->template->pagetext = $settingsModel->getPageText(3, $settingsModel->getLangId($this->lang))->content;
	}


	public function detailNFormSubmited(NAppForm $form)
	{
		$model1 = $this->getInstanceOf('UserModel');
		$model = $this->getInstanceOf('ProductModel');
		if(!$this->user->isLoggedIn())
		{
			$pole = $model1->setGuest();
			$this->user->setAuthenticator($this->getInstanceOf('Authenticator'));
			$this->user->login($pole['username'],$pole['password']);
		}
		foreach($_POST as $key=>$val)
		{
			$pole = explode('_',$key);
			if($pole[0] == 'sub')
			{
				$model->addToBasket($_POST['var_'.$pole[1]],$pole[1],$this->user->getIdentity()->data['id']);
				break;
			}
		}
		$this->redirect('this');
	}

	public function handleRegister($id)
	{
		$model = $this->getInstanceOf('UserModel');
		$model->endRegistration($id);
		$this->flashMessage('Registrace byla úspěšně dokončena, nyní se můžete přihlásit.');
		$this->redirect('default');
	}

	public function handleSetAktualFoto($id,$foto)
	{
		$this->template->aktualfoto = $foto;
		if($this->isAjax())$this->invalidateControl();
		else $this->redirect('this');
	}

	public function handlePrevod($path)
	{
		$model = $this->getInstanceOf('KategorieModel');
		$path = $model->getPathFromId($path,$this->lang);
		$this->redirect('kategorie',array('path'=>$path));
	}

	public function handleActivate($pass)
	{
	  $model = $this->getInstanceOf('UserModel');
	  $model->activatePassword($pass);
	  $this->flashMessage('Vaše nové heslo bylo aktivováno, nyní se můžete přihlásit.');
	  $this->redirect('this');
	}

	public function handleShowMore($path)
	{
		$model1 = $this->getInstanceOf('KategorieModel');
		$id = $model1->getIdFromPath($path,$this->lang);
		$this->template->mkategorie = $model1->getCategory($id,$this->lang,false);
		$this->invalidateControl('popis');
	}

	private function setBalne()
	{
	  $session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('PaymentModel');
		$model2 = $this->getInstanceOf('ProductModel');
		if($this->user->isLoggedIn() && !$this->isAjax())
		{
			$cena = $model2->getBasket($this->user->getIdentity()->data['id']);
			$poleDodani = $model->getAllDodani($this->lang,$this->stat);
			if(isset($session->dodani))$aktualniDodani = $session->dodani;
			else $aktualniDodani = key($poleDodani);

			$platby = $model->getPayments($aktualniDodani,$cena['cena'],$this->lang,$this->stat);
			if(isset($session->platba) && in_array($session->platba, array_keys($platby)))$aktualniPlatba = $session->platba;
			else $aktualniPlatba = key($platby);

			$dodani = $model->getDodani($aktualniDodani,$this->lang,ProductModel::getWeight($this->userdata['id']),$this->stat);
			$platba = $model->getPayment($aktualniPlatba,$this->stat);

			if($cena['cena'] >= $dodani->zdarma_od)$this->template->balne = 0;
			else $this->template->balne = $dodani->cena;

			if(isset($platba->cena))$this->template->balne += $platba->cena;
		}
	}
}
