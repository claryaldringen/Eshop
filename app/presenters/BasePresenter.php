<?php
abstract class BasePresenter extends NPresenter{

	public $lang = 'cs';
	protected $userdata;
	private $models = array();

	public function startup()
	{
		parent::startup();
		$user = $this->getUser();
		$user->setAuthenticator($this->getInstanceOf('Authenticator'));
		if($this->user->isInRole(Authenticator::REGISTERED)) $this->userdata = $this->getUser()->getIdentity()->getData();
		else $this->userdata = NULL;
		$this->template->registerHelper('money', create_function('$s', 'return number_format($s, 0, NULL, " ");'));
		if($this->getName() != 'Frontend' && !$this->user->isInRole(Authenticator::ADMIN))
		{
			$this->redirect('Frontend:default');
		}
		$this->template->registerHelperLoader('MyHelpers::loader');
		CnbNette::register($this->getTemplate());
	}

	function beforeRender()
	{
		if (!NEnvironment::getSession()->isStarted()) {
        NEnvironment::getSession()->start();
		}

		$this->template->user = $this->user;
		$this->template->lang = $this->lang;
		$this->template->userdata = $this->userdata;
	}


	public function createComponentTextEditNForm()
	{
		$form = new NAppForm($this,'textEditNForm');
		$form->addHidden('type');
		$form->addHidden('id');
		$form->addHidden('lang');
		$form->addTextArea('obsah','');
		$form->onSuccess[] = array($this,'textEditNFormSubmited');
		return $form;
	}

	public function textEditNFormSubmited(NAppForm $form)
	{
		$values = $form->getValues();
		if($values['type'] == 'product')
		{
			$model = $this->getInstanceOf('ProductModel');
			$model->setPopis($values['id'],$values['obsah'],$values['lang']);
		}
		$this->redirect('this');
	}

	public function createComponentLoginNForm()
	{
		$form = new NAppForm($this,'loginNForm');
		$form->addText('nick','Uživatelské jméno:')
		  ->addRule(NForm::FILLED,'Musíte vyplnit uživatelské jméno!')
		  ->getControlPrototype()->class('vypln');
		$form->addPassword('password','Heslo:')
		  ->addRule(NForm::FILLED,'Musíte vyplnit heslo!')
		  ->getControlPrototype()->class('vypln');
		$form->addCheckbox('stay','neodhlašovat');
		$form->addSubmit('login','Přihlásit')->getControlPrototype()->class('log');
		$form->onSuccess[] = array($this,'loginNFormSubmited');
		return $form;
	}

	public function loginNFormSubmited(NAppForm $form)
	{
		$user = $this->getUser();
		$user->setAuthenticator($this->getInstanceOf('Authenticator'));
		try{
			if($user->isInRole('3'))$oldid = $user->getIdentity()->data['id'];
			$user->login($form['nick']->getValue(),$form['password']->getValue());
		}catch(NAuthenticationException $e){
			$this->flashMessage($e->getMessage(),'err');
			$this->redirect('this');
		}
		if(isset($oldid))
		{
			$model = $this->getInstanceOf('UserModel');
			$model->setBasket($oldid,$user->getIdentity()->data['id']);
			$session = NEnvironment::getSession('shop');
			if($session->toorder)$this->redirect('order');
		}
		if($user->isInRole(Authenticator::ADMIN))$this->redirect('Kategorie:default');
		else $this->redirect('this');
	}

	public function createComponentSearchForm()
	{
		$form = new NAppForm($this,'searchForm');
		$form->addText('search','Hledaný výraz:')
		  ->addRule(NForm::FILLED,'Musíte vyplnit hledaný výraz.')
		  ->getControlPrototype()->class('vypln');
		$form->addSubmit('ok','Hledat')->getControlPrototype()->class('hledat');
		$form->onSuccess[] = array($this,'searchFormSubmited');
		return $form;
	}

	public function searchFormSubmited(NAppForm $form)
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$session->search = $model->search($form['search']->getValue(),$this->lang);
		$this->redirect('Frontend:search');
	}

	public function createComponentGa()
	{
		$ga = new GAcontrol($this,'ga');
		return $ga;
	}

	protected function createComponentCnb()
	{
		$cnb = new Cnb($this->context->params['tempDir']);
		return $cnb;
	}

	public function handleLogout()
	{
		$user = NEnvironment::getUser();
		$user->Logout();
		if(!in_array($this->getAction(),array('order','basket','orderend')))$this->redirect('this');
		else $this->redirect('default');
	}

	public function handleNoSpam($id)
	{
		$model = $this->getInstanceOf('UserModel');
		$model->updateUser(array('id'=>$id,'news'=>0));
		$this->flashMessage('Zasílání obchodních sdělení bylo zrušeno.');
		$this->redirect('this');
	}

	public function handleSetPaymentFromBank()
	{
		$model = $this->getInstanceOf('OrdersModel');
		try{
			$res = $model->setPaymentFromBank($_POST);
			if($res)echo 'ok';
			else echo 'failed';
		}catch(Exception $e){
			echo 'error';
		}
		die;
	}

	public function inWords($number)
	{
		$helpers = new Helpers();
		return $helpers->inWods($number);
	}

	/**
	 * Tovarni metoda pro instancovani modelu.
	 *
	 * @param String $model
	 * @return $model
	 */
	public function getInstanceOf($model)
	{
		$mf = ModelFactory::getInstance($this->context);
		return $mf->getInstanceOf($model);
	}
}
