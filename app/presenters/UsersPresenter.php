<?php

class UsersPresenter extends BasePresenter{

	private $filter = array();
	private $sort = 'id ASC';

	public function createComponentEmailNForm()
	{
		$form = new NAppForm($this,'emailNForm');
		$form->addText('emailsub','Předmět:',112);
		$form->addTextArea('email','');
		$form->onSuccess[] = array($this,'emailNFormSubmited');
		return $form;
	}

	public function emailNFormSubmited(NAppForm $form)
	{
		$values = $form->getValues();
		$model = $this->getInstanceOf('MailModel');
		$model->setEmails($this,$values['emailsub'],$values['email']);
		$this->redirect('this');
	}

	public function renderDefault($sort = 'id ASC')
	{
		if(!$this->isAjax())$this->sort = $sort;
		$model = $this->getInstanceOf('UserModel');
		$this->template->columns = explode(',', $this->context->params['users']['columns']);
		$this->template->users = $model->getUsers($this->filter,$this->sort);
	}

	public function handleShowNewEmail()
	{
		$this->template->showEmailDialog = true;
		$this->invalidateControl('emailDialog');
	}

	public function handleFilter($name)
	{
		$names = explode(',',$name);
		foreach($names as $name)
		{
			$this->filter[$name] = $_POST['val'];
		}
		$this->invalidateControl('table');
	}

	public function handleSort($sort)
	{
		$this->sort = $sort;
		if($this->isAjax())$this->invalidateControl('table');
		else $this->redirect('this',array('sort'=>$sort));
	}
}
