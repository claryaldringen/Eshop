<?php

class PaymentModel extends BaseModel{

	public function setPayment($values, $lang)
	{
		$countries = array();
		$model = $this->getInstanceOf('UserModel');
		foreach($model->getCountries(TRUE) as $key=>$val)
		{
			if($values[$key.'_check'])$countries[] = $key;
			unset($values[$key.'_check']);
		}
		
		if($values['id'])
		{
			$id = $values['id'];
			dibi::query("UPDATE platby SET jmeno=%s,jmeno_$lang=%s,cena_do=%i WHERE id=%i", $values['jmeno'], $values['jmeno_'.$lang],$values['cena_do'], $id);
		}else{
			$insert = array('jmeno_'.$lang=>$values['jmeno_'.$lang],'cena_do'=>$values['cena_do'],'jmeno'=>$values['jmeno']);
			dibi::query("INSERT INTO platby",$insert);
			$id = dibi::getInsertId();
		}
		
		dibi::query("DELETE FROM platba_stat WHERE platba=%i",$id);
		foreach($countries as $country)
		{
			dibi::query("INSERT INTO platba_stat",array('stat'=>$country,'platba'=>$id,'ncena'=>$values[$country.'_ncena'],'cena'=>$values[$country.'_cena']));
		}
		
		return $id;
	}
	
	public function setDodani($values,$lang)
	{
		$countries = array();
		$model = $this->getInstanceOf('UserModel');
		if($values['id'])
		{
			$id = $values['id'];
			dibi::query("UPDATE dodani SET jmeno_$lang=%s,zdarma_od=%i,stav=%s WHERE id=%i",$values['jmeno'],$values['zdarma_od'],$values['stav'],$id);
		}else{
			$insert = array('jmeno_'.$lang=>$values['jmeno'],'zdarma_od'=>$values['zdarma_od'],'stav'=>$values['stav']);
			dibi::query("INSERT INTO dodani",$insert);
			$id = dibi::getInsertId();	
		}
		
		foreach($model->getCountries(TRUE) as $key=>$val)
		{
			if($values[$key.'_check'])
			{
				$countries[] = array(
					'dodani'=>$id,
					'stat'=>$key,
					'1_ncena'=>	$values[$key.'_1_ncena'],
					'1_cena'=>	$values[$key.'_1_cena'],
					'2_ncena'=>	$values[$key.'_2_ncena'],
					'2_cena'=>	$values[$key.'_2_cena'],
					'3_ncena'=>	$values[$key.'_3_ncena'],
					'3_cena'=>	$values[$key.'_3_cena'],
					'5_ncena'=>	$values[$key.'_5_ncena'],
					'5_cena'=>	$values[$key.'_5_cena'],
					'7_ncena'=>	$values[$key.'_7_ncena'],
					'7_cena'=>	$values[$key.'_7_cena'],
					'10_ncena'=>	$values[$key.'_10_ncena'],
					'10_cena'=>		$values[$key.'_10_cena'],
					'12_ncena'=>	$values[$key.'_12_ncena'],
					'12_cena'=>		$values[$key.'_12_cena'],
					'15_ncena'=>	$values[$key.'_15_ncena'],
					'15_cena'=>		$values[$key.'_15_cena'],
					'20_ncena'=>	$values[$key.'_20_ncena'],
					'20_cena'=>		$values[$key.'_20_cena'],
					'25_ncena'=>	$values[$key.'_25_ncena'],
					'25_cena'=>		$values[$key.'_25_cena'],
					'30_ncena'=>	$values[$key.'_30_ncena'],
					'30_cena'=>		$values[$key.'_30_cena'],
					'35_ncena'=>	$values[$key.'_35_ncena'],
					'35_cena'=>		$values[$key.'_35_cena'],
					'40_ncena'=>	$values[$key.'_40_ncena'],
					'40_cena'=>		$values[$key.'_40_cena'],
					'50_ncena'=>	$values[$key.'_50_ncena'],
					'50_cena'=>		$values[$key.'_50_cena']);
			}
		}
		
		dibi::query("DELETE FROM dodani_stat WHERE dodani=%i",$id);
		foreach($countries as $country)
		{
			dibi::query("INSERT INTO dodani_stat",$country);
		}
	}
	
	public function getAllPayments($lang)
	{
		$result = dibi::query("SELECT id,jmeno FROM platby WHERE status='ok'");
		return $result->fetchPairs('id','jmeno');
	}
	
	public function getAllDodani($lang,$stat,$frontend = true)
	{
		if($frontend)$result = dibi::query("SELECT D.id,jmeno_$lang AS jmeno FROM dodani D JOIN dodani_stat S ON S.dodani=D.id JOIN dodaniplatba P ON P.dodani=S.dodani JOIN platba_stat  T ON T.platba=P.platba WHERE D.status='ok' AND T.stat=S.stat AND S.stat=%i",$stat);
		else $result = dibi::query("SELECT id,jmeno_$lang AS jmeno FROM dodani"); 
		return $result->fetchPairs('id','jmeno');
	}
	
	public function getPayment($id,$country = NULL)
	{
		$model = $this->getInstanceOf('UserModel');
		$result = dibi::fetch("SELECT * FROM platby WHERE id=%i",$id);	
		foreach($model->getCountries(TRUE) as $key=>$val)
		{
			$result[$key.'_check'] = $model->isPossiblePay($result->id,$key);
			$res = dibi::fetch("SELECT cena,ncena FROM platba_stat WHERE platba=%i AND stat=%i",$id, $key);
			$result[$key.'_cena'] = $res['cena'];
			$result[$key.'_ncena'] = $res['ncena'];
		}
		
		if(isset($country))
		{
			$result['cena'] = $result[$country.'_cena'];	
			$result['ncena'] = $result[$country.'_ncena'];
		}
		
		return $result;
	}
		
	public function getPayments($dodani,$cena,$lang,$stat)
	{
		$result = dibi::query("SELECT P.id,P.jmeno_$lang AS jmeno FROM dodaniplatba D JOIN platby P ON D.platba=P.id JOIN platba_stat S ON S.platba=P.id WHERE P.status='ok' AND D.dodani=%i AND P.cena_do > %f AND stat=%i",$dodani,$cena,$stat);	
		return $result->fetchPairs('id','jmeno');
	}
	
	public function getDodani($id,$lang,$vaha= 0,$stat = NULL)
	{
		if(!$id)throw new InvalidArgumentException('No delivery ID set.',666);
		$model = $this->getInstanceOf('UserModel');
		$result = dibi::fetch("SELECT id,jmeno_$lang AS jmeno,stav,zdarma_od FROM dodani WHERE id=%i",$id);	
		foreach($model->getCountries(TRUE) as $key=>$val)
		{
			$result[$key.'_check'] = $model->isPossibleDel($result->id,$key);
			$result2 = dibi::fetch("SELECT * FROM dodani_stat WHERE dodani=%i AND stat=%i", $result->id, $key);
			if(isset($result2->dodani))
			{
				unset($result2['dodani']);
				unset($result2['stat']);
				unset($result2['zdarma_od']);
				foreach($result2 as $weight => $cena)
				{
					$result[$key.'_'.$weight] = $cena;
					$hmotnost = explode('_',$weight);	
					if(isset($stat) && ($hmotnost[0]*1000) > $vaha && $stat==$key && !isset($result['cena']))
					{
						if($hmotnost[1] == 'ncena')$result['ncena'] = $cena;
						if($hmotnost[1] == 'cena')$result['cena'] = $cena;
						
					}
				}
			}
		}
		return $result;
	}
	
	public function getPayments2()
	{
		$pole = array();
		$result1 = dibi::query("SELECT id FROM platby");
		foreach ($result1 as $info1)
		{
			$result2 = dibi::query("SELECT id FROM dodani");
			foreach ($result2 as $info2)
			{
				$result = dibi::fetch("SELECT id FROM dodaniplatba WHERE dodani=%i AND platba=%i",$info2->id,$info1->id);	
				if(isset($result->id))$pole[$info1->id][$info2->id] = $result->id;
				else $pole[$info1->id][$info2->id] = 0;
			}	
		}
		return $pole;		
	}
	
	public function setPaymentType($platba,$dodani)
	{
		$result = dibi::fetch("SELECT dodani FROM dodaniplatba WHERE dodani=%i AND platba=%i",$dodani,$platba);	
		if(isset($result->dodani))dibi::query("DELETE FROM dodaniplatba WHERE dodani=%i AND platba=%i",$dodani,$platba);
		else dibi::query("INSERT INTO dodaniplatba",array('dodani'=>$dodani,'platba'=>$platba));				
	}

	public function getEmail($id,$typ,$lang)
	{
		if($typ == 'dodani')$result = dibi::fetch("SELECT id,email_$lang AS email,emailsub_$lang AS emailsub FROM dodani WHERE id=%i",$id);
		if($typ == 'platba')$result = dibi::fetch("SELECT id,email_$lang AS email,emailsub_$lang AS emailsub FROM dodaniplatba WHERE id=%i",$id);
		$result['typ'] = $typ;
		return $result;
	}
	
	public function setEmail($data)
	{
		$id = $data['id'];
		$typ = $data['typ'];
		unset($data['id']);
		unset($data['typ']);
		if($typ == 'dodani')$result = dibi::query("UPDATE dodani SET ",$data," WHERE id=%i",$id);
		if($typ == 'platba')$result = dibi::query("UPDATE dodaniplatba SET ",$data," WHERE id=%i",$id);
	}
	
	public function deletePlatba($id)
	{
		dibi::query("UPDATE platby SET status='del' WHERE id=%i",$id);
		dibi::query("DELETE FROM dodaniplatba WHERE platba=%i",$id);
	}

	public function deleteDodani($id)
	{
		dibi::query("UPDATE dodani SET status='del' WHERE id=%i",$id);
		dibi::query("DELETE FROM dodaniplatba WHERE dodani=%i",$id);
	}
}
