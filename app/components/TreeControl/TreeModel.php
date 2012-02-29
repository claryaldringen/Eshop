<?php

/** 
 * @author Clary
 * 
 * 
 */
class TreeModel extends BaseModel{
  
  private $lang;
  private $cats;
  
  public function getTree($path,$lang)
  {
    
    $this->cats = explode('/',$path);
    if(!is_array($this->cats))$this->cats = array($path);
    $this->lang = $lang;
    return $this->getVetev(0,0);
  }
  
  private function getVetev($vlastnik,$level)
  {
    $model = $this->getInstanceOf('KategorieModel');
    $tree = array();
    $result = dibi::query("SELECT id,jmeno_$this->lang AS jmeno,link_$this->lang AS link FROM categories WHERE vlastnik=%i AND status='ok' AND type='normal' ORDER BY sort",$vlastnik)->fetchAll();
    foreach($result as $row)
    {
      $vetev = array();
      $vetev['jmeno'] = $row->jmeno;
      $vetev['link'] = $model->getPathFromId($row->id, $this->lang);
      $vetev['level'] = ($level+1);
      if(isset($this->cats[$level]) && $this->cats[$level] == $row->link)$vetev['child'] = $this->getVetev($row->id,($level+1));
      $tree[] = $vetev;
    }
    return $tree;
  }
}
