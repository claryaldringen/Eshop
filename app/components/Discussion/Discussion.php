<?php

class Discussion extends NControl{
	
	private $presenter;
	public $product;
	private $lang;
	
	public function __construct(NPresenter $presenter,$name,$lang = 'cs')
	{
		parent::__construct($presenter,$name);
		$session = NEnvironment::getSession('shop');
		$this->product = $session->actualItem;
		$this->presenter = $presenter; 
		$this->lang = $lang;
	}
	
	public function createComponentDiscussForm()
	{
		$form = new NAppForm($this,'discussForm');
		$form->addTextArea('text','Text příspěvku:')->addRule(NForm::FILLED,'Musíte napsat nějakou zprávu.');
		$form->addHidden('product');
		$form->addCheckbox('mail','Chci dostávat upozornění na nové příspěvky v této diskuzi na e-mail.')->setDefaultValue(true);
		$form->addSubmit('send','Odeslat')->getControlPrototype()->class('kosikbutt');
		$form->onSubmit[] = array($this,'discussFormSubmited');
		return $form;
	}
	
	public function discussFormSubmited(NAppForm $form)
	{
		$model = new DiscussionModel();
		$values = $form->getValues();
		$model->setMessage($this->presenter,$values['text'],$values['product'],$values['mail'],$this->lang);
		$this->presenter->redirect('this');
	}
	
	public function render()
	{
		$model = new DiscussionModel($this->presenter->context);
		
		$form = $this->getComponent('discussForm');
		$form['product']->setValue($this->product);
		
		$template = $this->createTemplate();
		$template->setFile(dirname(__FILE__).'/discussion.phtml');
		$template->messages = $model->getMessages($this->product,$this->lang);
		$template->user = $this->presenter->user;
		$template->render();
	}
	
	public function handleDelete($id)
	{
		$model = new DiscussionModel();
		$model->deleteMessage($id);
		$this->presenter->redirect('this');
	}
}
