<?php

class GalleryModel extends NObject{
	
	private $sekce;
	
	public function __construct($sekce = 0)
	{
		$config = NEnvironment::getConfig();
 
		try {
			dibi::connect($config->database);
		} catch(DibiDriverException $e) {
			$error = $e->getMessage();
		}
		
		$this->sekce = $sekce;
		$session = NEnvironment::getSession('gallery');
		if($session->section)$this->sekce = $session->section;
	}
	
	public function getFolders($parent)
	{
		$ret = array();
		$result = dibi::query("SELECT * FROM folders WHERE owner=%i and owner_sekce=%i ORDER BY sort",$parent,$this->sekce);
		foreach($result as $info)
		{
			$obj = new stdClass();
			$obj->id = $info->id;
			$obj->name = $info->name;
			$result2 = dibi::fetch("SELECT id FROM images WHERE owner=%i ORDER BY sort,id",$info->id);
			$obj->image = $result2->id;
			array_push($ret,$obj);
		}
		return $ret;
	}
	
	public function move($co,$kam)
	{
		$pole1 = explode('-',$co);
		$pole2 = explode('-',$kam);
		if($pole1[0] == 'f' && $pole2[0] == 'f')dibi::query("UPDATE folders SET owner=%i WHERE id=%i",$pole2[1],$pole1[1]);
		if($pole1[0] == 'i' && $pole2[0] == 'f')dibi::query("UPDATE images SET owner=%i WHERE id=%i",$pole2[1],$pole1[1]);
	}
	
	public function setFolderName($id,$name)
	{
		dibi::query("UPDATE folders SET name=%s WHERE id=%i",$name,$id);
	}
	
	public function setPopis($id,$name,$lang)
	{
		dibi::query("UPDATE images SET popis_".$lang."=%s WHERE id=%i",$name,$id);
	}
	
	public function getOwner($id)
	{
		$result = dibi::fetch("SELECT owner FROM folders WHERE id=%i",$id);
		return $result->owner;
	}
	
	public function getImageOwner($id)
	{
		$result = dibi::fetch("SELECT owner FROM images WHERE id=%i",$id);
		return $result->owner;
	}
	
	public function getImages($parent)
	{
		$result = dibi::query("SELECT * FROM images WHERE vlastnik=%i ORDER BY sort,id",$this->sekce);
		return $result->fetchAll();	
	}
	
	public function delete($co)
	{
		$pole1 = explode('-',$co);
		if($pole1[0] == 'f')$this->deleteFolder($pole1[1]);
		if($pole1[0] == 'i')$this->deleteImage($pole1[1]);
	}
	
	private function deleteImage($id)
	{
		dibi::query("DELETE FROM images WHERE id=%i",$id);
		unlink('./images/userimages/large'.$id.'.jpg');
		unlink('./images/userimages/medium'.$id.'.jpg');
		unlink('./images/userimages/mini'.$id.'.jpg');
	}
	
	private function deleteFolder($id)
	{
		$result = dibi::query("SELECT id FROM folders WHERE owner=%i",$id);
		foreach($result as $info)
		{
			$this->deleteFolder($info->id);
		}
		$result = dibi::query("SELECT id FROM images WHERE owner=%i",$pole1[1]);
		foreach($result as $info)
		{
			$this->deleteImage($info->id);
		}
		dibi::query("DELETE FROM folders WHERE id=%i",$id);
	}
	
	public function saveSort($sort)
	{
		$pole = explode("&",$sort);
		$i = 0;
		foreach($pole as $pol)
		{
  		$i++;
  		$pole2 = explode("=",$pol);
  		if($pole2[0] == 'i[]')mysql_query("UPDATE images SET sort=$i WHERE id='".$pole2[1]."';");
  		if($pole2[0] == 'f[]')mysql_query("UPDATE folders SET sort=$i WHERE id='".$pole2[1]."';");
		}
	}
	
	public function getImage($id)
	{
		$return = array();
		$end = false;
		
		$result = dibi::query("SELECT id,popis FROM images WHERE owner IN (SELECT owner FROM images WHERE id=%i) ORDER BY sort,id",$id);
		foreach($result as $info)
		{
			if($end){
				$return['next'] = $info->id;
				break;	
			}
			if($info->id == $id)
			{
				$end = true;
				$return['id'] = $id;
				$return['popis'] = $info->popis;	
			}
			elseif(!$end)$return['prev'] = $info->id;
		}
		return $return;
	}
	
	public function getNavigation($folder)
	{
		$navigace = array();
		$result = dibi::fetch("SELECT id,name,owner FROM folders WHERE id=%i",$folder);
		if(isset($result->owner) && $result->owner)$navigace = array_merge($navigace,$this->getNavigation($result->owner));
		if(isset($result))$navigace = array_merge($navigace,array($result));
		return $navigace;
	}
	
	public function setImage($values,$sekce)
	{
		$session = NEnvironment::getSession('gallery');
		$image = $values['image']->getImage();
		$image->resize(800,600);
		dibi::query("INSERT INTO images",array('vlastnik'=>$this->sekce));
		$id = dibi::getInsertId();
		$image->save('./images/uploaded/large'.$id.'.jpg');
		$image->resize(160,120);
		$image->save('./images/uploaded/medium'.$id.'.jpg');
		$image->resize(64,48);
		$image->save('./images/uploaded/mini'.$id.'.jpg');
	}
}
