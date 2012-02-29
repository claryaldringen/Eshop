<?php

class Gallery extends NControl{
	
	private $section = 0;
	
	public function handleMove()
	{
		$model = new GalleryModel();
		$model->move($_POST['co'],$_POST['kam']);
	}
	
	public function handleSetName($id)
	{
		$model = new GalleryModel();
		$model->setFolderName($id,$_POST['name']);
	}
	
	public function handleSetPopis($id,$lang)
	{
		$model = new GalleryModel();
		$model->setPopis($id,$_POST['popis'],$lang);
	}
	
	public function handleSetFolder($id)
	{
		$session = NEnvironment::getSession('gallery');
		$session->folder = $id;
		$this->redirect('this');
	}
	
	public function handleDelete()
	{
		$model = new GalleryModel();
		$model->delete($_POST['co']);
	}
	
	public function handleSort()
	{
		$model = new GalleryModel();
		$model->saveSort($_POST['data']);	
	}
	
	public function setSection($section)
	{
		$this->section = $section;
		$session = NEnvironment::getSession('gallery');
		$session->section = $section;
	}
	
	public function createComponentPanel()
	{
		$form = new NAppForm($this,'panel');
		$form->addFile('image','Obrázek:')->addRule(NForm::MIME_TYPE,'Soubor musí být obrázek','image/png,image/jpeg,image/gif');	
		$form->addSubmit('ok','OK');
		$form->onSubmit[] = array($this,'panelSubmited');
		return $form;
	}	
	public function panelSubmited(NAppForm $form)
	{
		$values = $form->getValues();
		$model = new GalleryModel();
		$model->setImage($values,$this->section);
		$session = NEnvironment::getSession('gallery');
		$session->show = true;
		$this->redirect('this');
	}
	
	public function render()
	{
		$session = NEnvironment::getSession('gallery');
		if(!$session->folder)$session->folder = 0;
		if(!$session->lock)$session->lock = false;
		$model = new GalleryModel($this->section);
		$model2 = new ProductModel();
		
		$template = $this->createTemplate();
		$template->setFile(dirname(__FILE__).'/gallery.phtml');
		//$template->folders = $model->getFolders($session->folder);
		//if($session->folder)$template->up = $model->getOwner($session->folder);
		$template->images = $model->getImages($session->folder);
		$template->lock = $session->lock;
		$template->langs = $model2->getLanguages();
		//$template->navigation = $model->getNavigation($session->folder);
		$template->render();
	}
}
