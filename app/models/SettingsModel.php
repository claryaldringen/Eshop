<?php

class SettingsModel extends BaseModel{

	public function getTax()
	{
	  $pole = array();
	  $result = dibi::query("SELECT * FROM tax")->fetchAll(); 
	  foreach($result as $info)
	  {
	    $pole[$info->stat][$info->product] = $info->tax;
	  }
	  return $pole;
	}
	
	public function getCountries($activ=0)
	{
	  if($activ == 0)$in = '0,1';
	  else $in = '1';
	  return dibi::query("SELECT iso,numcode,printable_name AS name,activated FROM country WHERE numcode IS NOT NULL AND activated IN (".$in.")")->fetchAll(); 
	}
	
	public function addLangRow()
	{
		dibi::query("INSERT INTO languages VALUES ()");
	}
	
	public function getLangs()
	{
		return dibi::query("SELECT * FROM languages")->fetchAll();	
	}
	
	public function setLang($data,$id)
	{
		if(isset($data['zkratka']))$zk = dibi::fetch("SELECT zkratka FROM languages WHERE id=%i",$id)->zkratka;
		dibi::query("UPDATE languages SET ",$data," WHERE id=%i",$id);
		if(isset($zk))
		{
			$result = dibi::query("SELECT [table],[column],type FROM langconvert");
			foreach($result as $info)
			{
				if($zk && $data['zkratka'])dibi::query("ALTER TABLE",$info->table,"CHANGE COLUMN",$info->column."_".$zk,$info->column."_".$data['zkratka'],$info->type);
				elseif($zk)dibi::query("ALTER TABLE",$info->table,"DROP COLUMN",$info->column."_".$zk);
				elseif($data['zkratka'])dibi::query("ALTER TABLE",$info->table,"ADD COLUMN",$info->column."_".$data['zkratka'],$info->type);
			}
		}
	}
	
	public function setText($key,$lang)
	{
		dibi::query("UPDATE settings SET text_$lang=%s WHERE id=1",$key);
	}
	
	public function getText($lang)
	{
		$result = dibi::fetch("SELECT text_$lang AS text FROM settings WHERE id=1");
		if(isset($result->text))return $result->text;
	}
	
	public function parsePost($data)
	{
	  foreach($data as $key=>$val)
	  {
	    $pole = explode('-',$key);
	    if(count($pole) == 2)
	    {
	      dibi::query("DELETE FROM tax WHERE stat=%i AND product=%i",$pole[0],$pole[1]);
	      dibi::query("INSERT INTO tax",array('stat'=>$pole[0],'product'=>$pole[1],'tax'=>$val));
	    }
	  }  
	}
	
	public function activateCountry($iso)
	{
	  dibi::query("UPDATE country SET activated=NOT(activated) WHERE iso=%s",$iso);  
	}
	
	public function getLocalSettings()
	{
		NEnvironment::getHttpContext();
	}
}
