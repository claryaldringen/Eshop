<?php

class KategoriePresenter extends BasePresenter{

	/** @persistent */
  public $id = 0;

	public function createComponentMultiFile()
	{
		$exts = array('jpg','jpeg','gif','png','bmp');
		$multifile = new MultiFile($this,'multiFile','Nahrát soubory',$exts,1,1024*1024*1024*50);
		$multifile->onAllSubmit[] = array($this,'multiFileAllSubmit');
		return $multifile;
	}

	public function multiFileAllSubmit($files)
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		foreach($files as $file)
		{
			$model->setImage($session->actualItem,$file);
		}
		$this->template->showItemDialog = 1;
		$this->handleShowItemDialog($session->actualItem);
	}

	public function createComponentMultiFile2()
	{
		$exts = array('jpg','jpeg','gif','png','bmp');
		$multifile = new MultiFile($this,'multiFile2','Nahrát soubory',$exts,1,1024*1024*1024*50);
		$multifile->onAllSubmit[] = array($this,'multiFileAllSubmit2');
		return $multifile;
	}

	public function multiFileAllSubmit2($files)
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		foreach($files as $file)
		{
			$model->setImage($session->actualItem,$file,'specialni');
		}
		$this->redirect('this');
	}

	public function createComponentDodaniNForm()
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$form = new NAppForm($this,'dodaniNForm');
		$form->addText('dph','DPH:',1)
			->addRule(NForm::RANGE,'DPH musí být v rozmezí %d až %d.',array(0,100))
			->setDefaultValue($model->getDph($session->actualItem));
		$form->addText('dodani','Doba dodání:',1)
			->addRule(NForm::INTEGER,'Doba dodání musí být celé číslo.')
			->setDefaultValue($model->getDodani($session->actualItem));

		$form['dph']->getControlPrototype()->onChange("$('#frm-dodaniNForm').ajaxSubmit()");
		$form['dodani']->getControlPrototype()->onChange("$('#frm-dodaniNForm').ajaxSubmit()");

		$form->onSuccess[] = array($this,'dodaniNFormSubmited');
		return $form;
	}

	public function dodaniNFormSubmited(NAppForm $form)
	{
		$session = NEnvironment::getSession('shop');
		$values = $form->getValues();
		$model = $this->getInstanceOf('ProductModel');
		$model->setDph($session->actualItem,$values['dph']);
		$model->setDodani($session->actualItem,$values['dodani']);
		die;
	}

	public function createComponentDiscussion()
	{
		$discussion = new Discussion($this,'discussion',$this->lang);
		return $discussion;
	}

	public function createComponentPopisNForm()
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$form = new NAppForm($this,'popisNForm');
		$form->addTextArea('popis','')
			->setDefaultValue($model->getPopis($session->actualItem,$this->lang));
		$form->onSuccess[] = array($this,'popisNFormSubmited');
		return $form;
	}

	public function popisNFormSubmited(NAppForm $form)
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$model->setPopis($form['popis']->getValue(),$session->actualItem,$this->lang);
		$this->redirect('this');
	}

	public function createComponentKomplementForm()
	{
		$session = NEnvironment::getSession('shop');

		$model = $this->getInstanceOf('KategorieModel');
		$cats = $model->getAllCats($this->lang);

		if($session->actualCat)$cat = $session->actualCat;
		else $cat = implode(',',array_keys($cats));
		$model = $this->getInstanceOf('ProductModel');

		$items = $model->getItems($cat,$this->lang,'jmeno');

		$form = new NAppForm($this,'komplementForm');
		$form->addSelect('categ','Kategorie',$cats)->getControlPrototype()->onChange("$('#frm-komplementForm').ajaxSubmit()");
		$form->addMultiSelect('items','Položky:',$items,19);//->addRule(NForm::FILLED,'Musíte vybrat alespoň jednu položku.');
		$form->addSubmit('komp','Přidat do komplementů');
		$form->addSubmit('supl','Přidat do suplementů');
		$form->onSuccess[] = array($this,'komplementFormSubmited');
		$form['categ']->setDefaultValue($session->actualCat);
		return $form;
	}

	public function komplementFormSubmited(NAppForm $form)
	{
		$session = NEnvironment::getSession('shop');
		if($this->isAjax())
		{
			$session->actualCat = $form['categ']->getValue();
			$this->redirect('this');
		}else{
			$model = $this->getInstanceOf('ProductModel');
			if($form['komp']->isSubmittedBy())$model->setComplements($form['items']->getValue(),$session->actualItem);
			if($form['supl']->isSubmittedBy())$model->setSuplements($form['items']->getValue(),$session->actualItem);
		}
		$this->redirect('this');
	}

	public function createComponentCompForm()
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$items = $model->getComplements($session->actualItem,'comp',$this->lang);
		$form = new NAppForm($this,'compForm');
		$form->addMultiSelect('items','Komplementy:',$items,10)->addRule(NForm::FILLED,'Musíte vybrat alespoň jednu položku.');
		$form->addSubmit('del','Odebrat z komplementů');
		$form->onSuccess[] = array($this,'compFormSubmited');
		return $form;
	}

	public function compFormSubmited(NAppForm $form)
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$model->remFromComp($form['items']->getValue(),'comp',$session->actualItem);
		$this->redirect('this');
	}

	public function createComponentSupForm()
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$items = $model->getComplements($session->actualItem,'supl',$this->lang);
		$form = new NAppForm($this,'supForm');
		$form->addMultiSelect('items','Suplementy:',$items,9)->addRule(NForm::FILLED,'Musíte vybrat alespoň jednu položku.');
		$form->addSubmit('del','Odebrat ze suplementů');
		$form->onSuccess[] = array($this,'supFormSubmited');
		return $form;
	}

	public function supFormSubmited(NAppForm $form)
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$model->remFromComp($form['items']->getValue(),'supl',$session->actualItem);
		$this->redirect('this');
	}

	public function createComponentSpecialForm1()
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$form = new NAppForm($this,'specialForm1');
		$form->addText('newSpecial','Nový parametr:')
			->addRule(NForm::FILLED,'Musíte vyplnit název parametru.')
			->addRule(~NForm::IS_IN,'Parametr s tímto názvem již existuje.',$model->getSpecials($session->actualItem,$this->lang));
		$form->addSubmit('add','Přidat');
		$form->onSuccess[] = array($this,'specialForm1Submited');
		return $form;
	}

	public function specialForm1Submited(NAppForm $form)
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$model->setNewSpecial($form['newSpecial']->getValue(),$session->actualItem,$this->lang);
		$this->redirect('this');
	}

	public function createComponentSpecialForm2()
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$form = new NAppForm($this,'specialForm2');
		$form->addSelect('specials','Parametry:',$model->getSpecials($session->actualItem,$this->lang),17)->addRule(NForm::FILLED,'Musíte vybrat nějaký paramter.');
		$form['specials']->getControlPrototype()->onChange('$("#frm-specialForm2").ajaxSubmit()');
		$form->addSubmit('del','Odebrat');
		$form->onSuccess[] = array($this,'specialForm2Submited');
		return $form;
	}

	public function specialForm2Submited(NAppForm $form)
	{
		if($this->isAjax())
		{
			$id_spec = $form['specials']->getValue();
			$model = $this->getInstanceOf('ProductModel');
			$form2 = $this->getComponent('specialForm3');
			$form2->setDefaults($model->getSpecial2($id_spec));
			$form2['id_spec']->setValue($id_spec);
			$this->invalidateControl('specialForm3');
		}else{
			$model = $this->getInstanceOf('ProductModel');
			$model->deleteSpecial($form['specials']->getValue());
			$this->redirect('this');
		}
	}

	public function createComponentSpecialForm3()
	{
		$form = new NAppForm($this,'specialForm3');
		$form->addSelect('typ','Typ:',array(1=>'Text 1 řádek',2=>'Text více řádků',3=>'Možnosti - 1 správná',4=>'Možnosti - více správných',5=>'Vložení souborů'));
		$form->addText('values','Hodnoty:')
			->addConditionOn($form['typ'],NForm::IS_IN,array(3,4))
			->addRule(NForm::FILLED,'Musíte vyplnit hodnoty.');
		$form->addCheckbox('filled','Musí být vyplněno');
		$form->addRadioList('number','',array(0=>'Nemusí být číslo','float'=>'Musí být reálné číslo','integer'=>'Musí být celé číslo'));
		$form->addText('od','Rozsah od:');
		$form->addText('do','Rozsah do:');
		$form->addHidden('id_spec')->addRule(NForm::FILLED,'Musíte vybrat nějaký parametr v tabulce vlevo.');
		$form->addSubmit('ok','OK');
		$form->onSuccess[] = array($this,'specialForm3Submited');
		return $form;
	}

	public function specialForm3Submited(NAppForm $form)
	{
		$model = $this->getInstanceOf('ProductModel');
		$model->setSpecial2($form->getValues());
		$this->redirect('this');
	}

	public function createComponentSpecialForm4()
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$form = new NAppForm($this,'specialForm4');
		$form->addTextArea('popis','Vysvětlující popis',40,6)->setDefaultValue($model->getSpecPopis($session->actualItem,$this->lang));
		$form->addSubmit('ok','Uložit');
		$form->onSuccess[] = array($this,'specialForm4Submited');
		return $form;
	}

	public function specialForm4Submited(NAppForm $form)
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$model->setSpecPopis($form['popis']->getValue(),$session->actualItem,$this->lang);
		$this->redirect('this');
	}

	public function createComponentSpecialForm5()
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$form = new NAppForm($this,'specialForm5');
		$form->addTextArea('condition','Cenové podmínky:',40,10)->setDefaultValue($model->getConditions($session->actualItem,$this->lang));
		$form->addSubmit('ok','Uložit');
		$form->onSuccess[] = array($this,'specialForm5Submited');
		return $form;
	}

	public function specialForm5Submited(NAppForm $form)
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$errarr = $model->setSpecConditions($form['condition']->getValue(),$session->actualItem,$this->lang);
		$err = $errarr[1];
		$row = $errarr[0];
		if($err == 1)$form->addError('Špatná synataxe podmínky, řádek '.$row);
		elseif($err == 2)$form->addError('Chybí klíčové slovo THEN, řádek '.$row);
		elseif($err == 3)$form->addError('Po THEN je očekáváno znaménko + nebo -, řádek '.$row);
		elseif($err == 4)$form->addError('Po znaménku + nebo - po THEN je očekáváno číslo, řádek '.$row);
		elseif($err == 5)$form->addError('Na konci podmínky musí být buď číslo nebo značka %, řádek '.$row);
		elseif($err == 6)$form->addError('V podmínce chybí uvozovky u parametru, řádek '.$row);
		else $this->redirect('this');
	}

	public function createComponentCatTextForm()
	{
		$model = $this->getInstanceOf('KategorieModel');

		$form = new NAppForm($this,'catTextForm');
		$form->addUpload('catimg', 'Ikona kategorie:');
		$form->addTextArea('cattext','')->setDefaultValue($model->getText($this->id,$this->lang));
		$form->onSuccess[] = array($this,'catTextFormSubmited');
		return $form;
	}

	public function catTextFormSubmited(NAppForm $form)
	{
		$model = $this->getInstanceOf('KategorieModel');
		$model->setImage($this->id,$form['catimg']->getValue());
		$model->setText($this->id,$form['cattext']->getValue(),$this->lang);
		$this->redirect('this');
	}

	public function renderDefault()
	{
		$session = NEnvironment::getSession('shop');
		if($session->bin)$status = 'del';
		else $status = 'ok';
		$model1 = $this->getInstanceOf('KategorieModel');
		$model2 = $this->getInstanceOf('ProductModel');
		$this->template->folders = $model1->getCategories($this->id,$this->lang,'normal',$status);
		$this->template->collections = $model1->getCategories(0,$this->lang,'collection');
		$this->template->items = $model2->getAdminProducts($this->id,$this->lang,$status,$this->context->params['kategorie']['sort']);
		$this->template->owner = $model1->getCategory($this->id,$this->lang);
		$this->template->status = $status;
	}

	public function renderIframe2()
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$this->template->properties = $model->getProperties($session->actualItem,$this->lang);
	}

	public function renderIframe3()
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$this->template->variants = $model->getVariants($session->actualItem,$this->lang);
	}

	public function renderIframe6()
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$this->template->spectype = $session->spectype;
		$this->template->cena = $model->getDefaultPrice($session->actualItem);
		$this->template->images = $model->getImages($session->actualItem,$this->lang,'specialni');
	}

	public function handleMove($files,$owner)
	{
		$items = array();
		$folders = array();
		$model1 = $this->getInstanceOf('KategorieModel');
		$model2 = $this->getInstanceOf('ProductModel');
		$pole = explode('_',$files);
		foreach($pole as $pol)
		{
			$obj = explode('-',$pol);
			if($obj[0] == 'f' && $obj[1] != $owner)$folders[] = $obj[1];
			if($obj[0] == 'i')$items[] = $obj[1];
		}
		$folders = array_unique($folders);
		$items = array_unique($items);
		$id = explode("-",$owner);
		if($id[0] == 'f')
		{
			$model1->move($folders,$id[1]);
			$model2->move($items,$id[1]);
			echo json_encode(array('stat'=>1));
		}
		if($id[0] == 'c')
		{
			$model2->moveToCollection($items,$id[1]);
			echo json_encode(array('stat'=>0));
		}
		die;
	}

	public function handleCopy($files)
	{
		$items = array();
		$model = $this->getInstanceOf('ProductModel');

		$pole = explode('_',$files);
		foreach($pole as $pol)
		{
			$obj = explode('-',$pol);
			if($obj[0] == 'i')$items[] = $obj[1];
		}
		$items = array_unique($items);
		$model->copyItems($items);
		$this->redirect('this');
	}

	public function handleDelete($files,$status = 'del')
	{
		$items = array();
		$folders = array();
		$model1 = $this->getInstanceOf('KategorieModel');
		$model2 = $this->getInstanceOf('ProductModel');
		$pole = explode('_',$files);

		foreach($pole as $pol)
		{
			$obj = explode('-',$pol);
			if($obj[0] == 'f')$folders[] = $obj[1];
			if($obj[0] == 'c')$folders[] = $obj[1];
			if($obj[0] == 'i')$items[] = $obj[1];
		}
		$folders = array_unique($folders);
		$items = array_unique($items);
		if(!empty($folders ))$model1->delete($folders,$status);
		if(!empty($items))$model2->delete($items,$this->id,$status);
		die(json_encode(array('stat'=>1)));
	}

	public function handleRename($name,$value)
	{
		$model1 = $this->getInstanceOf('KategorieModel');
		$model2 = $this->getInstanceOf('ProductModel');
		$obj = explode('-',$name);
		if($obj[0] == 'f' && $value)$model1->setName($value,$obj[1],$this->lang);
		if($obj[0] == 'i' && $value)$model2->setName($value,$obj[1],$this->lang);
		echo json_encode(array('stat'=>0));
		die;
	}

	public function handleNewCat($type)
	{
		$model = $this->getInstanceOf('KategorieModel');
		$model->newCat($this->id,$type);
		$this->redirect('this');
	}

	public function handleNewProd()
	{
		$model = $this->getInstanceOf('ProductModel');
		$model->newProd($this->id);
		$this->redirect('this');
	}

	public function handleShowOtherItem($item,$type)
	{
		$model = $this->getInstanceOf('ProductModel');
		$this->handleShowItemDialog($model->getOtherItem($item,$type));
	}

	public function handleShowItemDialog($item,$del=true,$tab=1)
	{
		$session = NEnvironment::getSession('shop');
		$session->actualItem = $item;
		//Smazani prazdnych radku ve vlastnostech a variantach
		$model = $this->getInstanceOf('ProductModel');
		if($del){
			$model->deleteEmptyProp($item);
			$model->deleteEmptyVar($item);
		}
		//Diskuze
		$discuss = $this->getComponent('discussion');
		$discuss->product = $item;
		//Predani promennych template
		$this->template->item = $item;
		$this->template->name = $model->getItemName($item,$this->lang);
		$this->template->images = $model->getImages($item,$this->lang);
		$this->template->properties = $model->getProperties($item,$this->lang);
		$this->template->showItemDialog = $tab;
		//Invalidace
		$this->invalidateControl('itemDialog');
	}

	public function handleShowText()
	{
		$this->template->showTextDialog = true;
		$this->invalidateControl('textDialog');
	}

	public function handleShowSort()
	{
		$model = $this->getInstanceOf('KategorieModel');
		$model2 = $this->getInstanceOf('ProductModel');
		$this->template->showSort = true;
		$this->template->cats = $model->getCategories($this->id,$this->lang);
		$this->template->items = $model2->getItems($this->id,$this->lang);
		$this->invalidateControl('sortDialog');
	}

	public function handleSaveSort()
	{
		$model = $this->getInstanceOf('ProductModel');
		$model->setSort($_POST['data']);
		echo json_encode(array('status'=>0));
		die;
	}

	public function handleSaveItemSort($type)
	{
		$model = $this->getInstanceOf('ProductModel');
		if($type == 'dir')$model->setCatSort($_POST['data']);
		if($type == 'file')$model->setItemSort($_POST['data']);
		echo json_encode(array('status'=>0));
		die;
	}

	public function handleDeleteImage($img)
	{
		$model = $this->getInstanceOf('ProductModel');
		$model->deleteImage($img);
		die(json_encode(array('status'=>0)));
	}

	public function handleRenameImg($img)
	{
		$model = $this->getInstanceOf('ProductModel');
		$model->renameImage($img,$_POST['name'],$this->lang);
		echo json_encode(array('status'=>0));
		die;
	}

	public function handleAddRow()
	{
		$model = $this->getInstanceOf('ProductModel');
		$session = NEnvironment::getSession('shop');
		$model->addProp($session->actualItem);
		$this->invalidateControl('table');
	}

	public function handleAddVarRow()
	{
		$model = $this->getInstanceOf('ProductModel');
		$session = NEnvironment::getSession('shop');
		$model->addVariant($session->actualItem);
		$this->invalidateControl('table');
	}

	public function handleSetPropName($pid)
	{
		$model = $this->getInstanceOf('ProductModel');
		$model->setProp(array('jmeno_'.$this->lang=>$_POST['name']),$pid);
		echo json_encode(array('status'=>0));
		die;
	}

	public function handleSetPropVal($pid)
	{
		$model = $this->getInstanceOf('ProductModel');
		$model->setProp(array('prop_'.$this->lang=>$_POST['val']),$pid);
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
		echo json_encode(array('status'=>0));
		die;
	}

	public function handleNewVariant()
	{
		$session = NEnvironment::getSession('shop');
		$data = $_POST;
		if(isset($data['kus']))$data['kus_'.$this->lang] = $data['kus'];
		unset($data['kus']);
		$model = $this->getInstanceOf('ProductModel');
		$model->newVariant($session->actualItem,$data);
		$this->invalidateControl('table');
	}

	public function handleShowCond($cond)
	{
		$session = NEnvironment::getSession('shop');
		$session->spectype = $cond;
		$this->template->spectype = $cond;
		$this->invalidateControl('specialForm3');
	}

	public function handleSetCena($cena)
	{
		$session = NEnvironment::getSession('shop');
		$model = $this->getInstanceOf('ProductModel');
		$model->setDefaultPrice($session->actualItem,$cena);
		echo json_encode(array('status'=>0));
		die;
	}

	public function handleToTheBasket()
	{
		$session = NEnvironment::getSession('shop');
		if($session->bin)$session->bin = false;
		else $session->bin = true;
		$this->redirect('this',array('id'=>0));
	}
}
