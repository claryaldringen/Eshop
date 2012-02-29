<?php

/** 
 * @author Clary
 * 
 * 
 */

class RatingControl extends NControl{

	private $user;
	private $presenter;
	private $id;
	
  public function __construct(NPresenter $presenter,$name,$user,$id)
	{
		parent::__construct($presenter,$name);
		$this->user = $user;
		$this->presenter = $presenter;
		$this->id = $id;
	}
  
	public function render()
  {
    $model = new RatingModel();
    $template = $this->createTemplate();
		$template->setFile(dirname(__FILE__).'/rating.phtml');
    $template->hlasy = $model->getHlasy($this->id);
    $template->hodnoceni = $model->getHodnoceni($this->id);
    $template->produkt = $this->id;
    $template->user = $this->presenter->user;
    $template->render();
  }
  
  public function handleOhodonot($hodnoceni,$prod)
  {
    $model = new RatingModel(); 
    $model->setHodnoceni($hodnoceni,$prod,$this->user);
    if($this->presenter->isAjax())$this->invalidateControl('hodnoceni'); 
    else $this->presenter->redirect('this'); 
  }
}
