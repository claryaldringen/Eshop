<?php

class UserModel extends BaseModel{

	public function setUser($data,NPresenter $presenter = NULL)
	{
		unset($data['id']);
		$heslo = $data['heslo'];
		$data['heslo'] = md5($heslo);
		$user = NEnvironment::getUser();
		if($user->isInRole('3'))
		{
			$userdata = $user->getIdentity()->getData();
			$data['registrovan'] = 1;
			$data['logincookie'] = md5($userdata['id']);
			dibi::query("UPDATE users SET ",$data," WHERE id=%i",$userdata['id']);
			$user->login($data['login'],$heslo);
			return 1;
		}else{
			dibi::query("INSERT INTO users",$data);
			$id = dibi::getInsertId();

			dibi::query("UPDATE users SET logincookie=MD5(id) WHERE id=%i",$id);
			if($this->context->params['registration']['emailconfirmation'] == 0)
			{
				dibi::query("UPDATE users SET registrovan=1 WHERE id=%i",$id);
				return 2;
			}

			$template = new NFileTemplate();
			$template->setFile(APP_DIR.'/templates/Mail/registration.phtml');
			$template->registerFilter(new NLatteFilter());
			$template->link = $presenter->link('//register!',array('id'=>$id));

			$maildata = $this->context->params['mail'];
			$mail = new NMail();
			$mail->setFrom($maildata->frommail,$maildata->fromname);
			$mail->addTo($data['email'],$data['jmeno'].' '.$data['prijmeni']);
			$mail->setSubject($maildata->registrationend);
			$mail->setHtmlBody($template);
			$mail->send();
		}
	}

	public function updateUser($data)
	{
		$id = $data['id'];
		unset($data['id']);
		unset($data['heslo2']);
		if(isset($data['heslo']) && $data['heslo'])$data['heslo'] = md5($data['heslo']);
		else unset($data['heslo']);
		dibi::query("UPDATE users SET ",$data,"WHERE id=%i",$id);
		$user = NEnvironment::getUser();
		$user->setAuthenticator($this->getInstanceOf('Authenticator'));
		$user->login(NULL,NULL,md5($id));
	}

	public function setGuest()
	{
		$login = time();
		dibi::query("INSERT INTO users",array('login'=>$login,'heslo'=>md5('heslo'),'registrovan'=>3,'lastlogin'=>time()));
		$id = dibi::getInsertId();
		dibi::query("UPDATE users SET logincookie=%s WHERE id=%i",md5($id), $id);
		return(array('username'=>$login,'password'=>'heslo'));
	}

	public function getLogins()
	{
		return dibi::query("SELECT id,login FROM users WHERE registrovan=1")->fetchPairs('id','login');
	}

	public function endRegistration($id)
	{
		dibi::query("UPDATE users SET registrovan=1 WHERE id=%i",$id);
	}

	public function getUsers($filter = array(),$sort = 'id ASC')
	{
		// Filtry
		$filt = array();
		foreach($filter as $col=>$val)
		{
			$filt[] = $col." LIKE '%".$val."%'";
		}
		$sql = implode(' OR ',$filt);

		// Razeni
		if(in_array($sort, array('pocet ASC','pocet DESC')))
		{
			$sort2 = $sort;
			$sort = 'id ASC';
		}elseif(in_array($sort, array('cena ASC','cena DESC'))){
			$sort3 = $sort;
			$sort = 'id ASC';
		}

		// Dotaz do DB
		if(empty($filter))$result = dibi::query("SELECT id,login,jmeno,prijmeni,email,telefon,ulice,mesto,psc,stat,lastlogin,ico,dic,firma FROM users WHERE registrovan=1 ORDER BY %sql",$sort)->fetchAll();
		else $result = dibi::query("SELECT id,login,jmeno,prijmeni,email,telefon,ulice,mesto,psc,stat,lastlogin,ico,dic,firma FROM users WHERE registrovan=1 AND (%sql) ORDER BY %sql",$sql,$sort)->fetchAll();

		// Zpracovani vysledku a pridani infa o objednavkach daneho uzivatele
		$sarr = array();
		foreach($result as $key=>$info)
		{
			$res = dibi::fetch("SELECT COUNT(*) AS pocet,SUM(cena) AS cena FROM objednavka O JOIN basket B ON B.id_obj=O.id WHERE O.stav != 'prijato' AND B.id_user=%i",$info->id);
			$info->pocet = $res->pocet;
			$info->cena = $res->cena;
			if(isset($sort2))$sarr[] = (int)$info->pocet;
			if(isset($sort3))$sarr[] = $info->cena;
			$info->lastlogin = date("d.m.Y H:i:s",$info->lastlogin);
			$result[$key] = $info;
		}

		// Trideni dle poctu objednavek ci jejich ceny u daneho uzivatele
		if(isset($sort2) || isset($sort3))
		{

			if(isset($sort2))$pole = explode(' ',$sort2);
			if(isset($sort3))$pole = explode(' ',$sort3);
			if($pole[1] == 'DESC')array_multisort($sarr,SORT_DESC,$result,SORT_DESC );
			if($pole[1] == 'ASC')array_multisort($sarr,SORT_ASC,$result,SORT_ASC );
		}

		return $result;
	}

	public function setBasket($oldid,$newid)
	{
		dibi::query("UPDATE basket SET id_user=%i WHERE id_user=%i",$newid,$oldid);
	}

	public function getUser($id)
	{
		return dibi::fetch("SELECT * FROM users WHERE id=%i",$id);
	}

	public function getCountries($act = FALSE)
	{
		if(!$act)$act = array('0',1);
		else $act = array(1);
		return dibi::query("SELECT printable_name,numcode FROM country WHERE activated IN %in AND numcode IS NOT NULL", $act)->fetchPairs('numcode','printable_name');
	}

	public function isPossiblePay($platba,$stat)
	{
		$result = dibi::fetch("SELECT stat FROM platba_stat WHERE platba=%i AND stat=%i",$platba,$stat);
		return isset($result->stat);
	}

	public function isPossibleDel($dodani,$stat)
	{
		$result = dibi::fetch("SELECT stat FROM dodani_stat WHERE dodani=%i AND stat=%i",$dodani,$stat);
		return isset($result->stat);
	}

	public function sendForgottenPassword($val,NPresenter $presenter = NULL)
	{
	  $res = dibi::fetch("SELECT id,login,jmeno,prijmeni,email FROM users WHERE login=%s OR email=%s",$val,$val);
	  if(isset($res->id))
	  {
			$template = new NFileTemplate();
			$template->setFile(APP_DIR.'/templates/Mail/password.phtml');
			$template->registerFilter(new NLatteFilter());
			$template->password = NStrings::lower(substr(md5(time()),0,5));
			$template->link = $presenter->link('//activate!',array('pass'=>md5($template->password)));
			$template->user = $res->login;

			dibi::query("UPDATE users SET newheslo=%s WHERE id=%i",md5($template->password),$res->id);

			$maildata = $this->context->params['mail'];
			$mail = new NMail();
			$mail->setFrom($maildata->frommail,$maildata->fromname);
			$mail->addTo($res['email'],$res['jmeno'].' '.$res['prijmeni']);
			$mail->setSubject($maildata->forgottenpassword);
			$mail->setHtmlBody($template);
			$mail->send();
			return true;
	  }else return false;
	}

	public function activatePassword($pass)
	{
	  dibi::query("UPDATE users SET heslo=newheslo WHERE newheslo=%s",$pass);
	}


	/**
	 * Vrati seznam adres vlozenych uzivatelem
	 *
	 * @return array
	 */
	public function getAddresses()
	{
		$adresy = array();
		$userId = $this->context->user->getIdentity()->data['id'];
		$adresa = dibi::fetch("SELECT jmeno,prijmeni,ulice,mesto,psc,printable_name AS stat,firma FROM users JOIN country ON stat=numcode WHERE id=%i", $userId);
		if($adresa->firma)$adresy[] = $adresa->firma . ', ' . $adresa->jmeno . ' ' . $adresa->prijmeni . ', ' . $adresa->ulice . ', ' . $adresa->psc . ' ' . $adresa->mesto . ', ' . $adresa->stat;
		else $adresy[] = $adresa->jmeno . ' ' . $adresa->prijmeni . ', ' . $adresa->ulice . ', ' . $adresa->psc . ' ' . $adresa->mesto . ', ' . $adresa->stat;

		$res = dibi::query("SELECT adresa FROM adresy WHERE user_id=%i", $userId)->fetchPairs(NULL, 'adresa');
		foreach($res as $adresa)
		{
			$adresy[]	= str_replace("\n", ", ", $adresa);
		}

		return $adresy;
	}
}
