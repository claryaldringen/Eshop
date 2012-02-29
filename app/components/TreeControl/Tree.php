<?php

/** 
 * @author Clary
 * 
 * 
 */
class Tree extends NControl{
  
  private $path = '';
  private $lang;
  
  public function __construct(NPresenter $presenter,$name,$path,$lang = 'cs')
  {
    parent::__construct($presenter,$name); 
    $this->lang = $lang;
    
  }
  
  public function setPath($path)
  {
    $this->path = $path;   
  }
  
  public function render()
  {
		$model = $this->presenter->getInstanceOf('TreeModel');
    $template = $this->createTemplate();
		$template->setFile(dirname(__FILE__).'/tree.phtml');
		$template->tree = $model->getTree($this->path,$this->lang);
		$template->render();
  }

}
