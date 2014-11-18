<?php

class OrdersModel extends BaseModel
{

	/**
	 * Vytvori z veci v kosiku objednavku.
	 *
	 * @param array $values pole s indexy dodani, platba, pozn, lang, mena, platba_cena, dodani_cena, platba_ncena, dodani_ncena
	 * @param int $user ID uzivatele, ktery objednavku vytvoril
	 * @param int $addcena Cena za dodaci sluzby
	 * @return int ID vytvorene objednavky
	 */
	public function setObjednavka($values,$user,$addcena)
	{
		$sklad = true;
		$pole = array();
		$max = 0;

		// Ulozeni informaci o soucasnem kurzu meny do objednavky
		$cnb = new Cnb($this->context->params['tempDir']);
		$money = $cnb->getMoney($values['mena']);
		$values['kurz'] = $money['rate'];
		$values['datum'] = date("Y-m-d H:i:s");
		if(isset($values['newdadresa']) && $values['newdadresa'])$values['dadresa'] = $this->setNewAddress($values['newdadresa']);
		if(isset($values['newfadresa']) && $values['newfadresa'])$values['fadresa'] = $this->setNewAddress($values['newfadresa']);

		unset($values['newdadresa']);
		unset($values['newfadresa']);

		dibi::query("INSERT INTO objednavka",$values);
		$id = dibi::getInsertId();

		$result = dibi::query("
		SELECT V.id,B.count,V.sklad,P.dodani
		FROM basket B
			JOIN variants V ON B.id_var=V.id
			JOIN products P ON P.id=V.vlastnik
		WHERE B.id_user=%i AND B.id_obj=0 ORDER BY P.dodani DESC",$user);

		foreach($result as $info)
		{
			if($info['dodani'] > $max)$max = $info['dodani'];
			$pole[$info['id']] = $info['count'];
			if($info['count'] > $info['sklad'])
			{
				$sklad = false;
				break;
			}
		}
		if($sklad)
		{
			foreach($pole as $key=>$value)
			{
				dibi::query("UPDATE variants SET sklad=sklad-%i WHERE id=%i",$value,$key);
				dibi::query("UPDATE basket SET sklad='ano' WHERE id_var=%i AND id_obj=0 AND id_user=%i",$key,$user);
			}

			$this->getInstanceOf('ProductModel')->checkSklad($pole);

			$result = dibi::fetch("
				SELECT SUM(V.cena*count*(1+dph/100)*(1-V.sleva/100)) AS cena
				FROM basket B
					JOIN variants V ON V.id=B.id_var
					JOIN products P ON P.id=V.vlastnik
				WHERE B.id_user=%i AND B.id_obj=0",$user);

			// Zjisteni dodani a platby
			$result->cena += $addcena;
			dibi::query("UPDATE objednavka SET expedice=0,cena=%f,datum=%s WHERE id=%i",$result->cena,date('Y-m-d H:i:s'),$id);
			dibi::query("UPDATE basket SET id_obj=%i WHERE id_obj=0 AND id_user=%i",$id,$user);

		}else{

			$result = dibi::fetch("
			SELECT SUM(V.cena*count*(1+dph/100)*(1-V.sleva/100)) AS cena
			FROM basket B
				JOIN variants V ON V.id=B.id_var
				JOIN products P ON P.id=V.vlastnik
			WHERE B.id_user=%i AND B.id_obj=0",$user);

			$result->cena += $addcena;
			dibi::query("UPDATE objednavka SET expedice=%i,cena=%f,datum=%s WHERE id=%i",$max,$result->cena,date('Y-m-d H:i:s'),$id);
			dibi::query("UPDATE basket SET id_obj=%i WHERE id_obj=0 AND id_user=%i",$id,$user);
		}

		// Nastaveni pevne ceny u vyrobku v jiz hotove objednavce
		$result = dibi::query("SELECT B.id,CEIL(V.cena*(1+P.dph/100)) AS cena,B.sleva FROM basket B JOIN variants V ON B.id_var=V.id JOIN products P ON V.vlastnik=P.id WHERE B.id_obj=%i", $id)->fetchAll();
		foreach($result as $info)
		{
			dibi::query("UPDATE basket SET price=%i,sleva=%i WHERE id=%i", $info->cena, $info->sleva, $info->id);
		}

		$this->getInstanceOf('MailModel')->setMail($id,'prijato');
		return $id;
	}

	public function getOrders($lang,$stav,$month,$year,$sort = 'O.id DESC')
	{
		$model = $this->getInstanceOf('PaymentModel');
		if($month == 0)
		{
			$dat1 = $year.'-01-01';
			$dat2 = ($year+1).'-01-01';
		}else{
			$dat1 = $year.'-'.$month.'-01';
			$dat2 = $year.'-'.($month+1).'-01';
		}
		$result = dibi::query("SELECT
			O.id,datum,pozn,expedice,O.stav,O.cena,O.zaplaceno,P.jmeno_$lang AS platba,
			D.jmeno_$lang AS dodani,D.zdarma_od,P.id AS pid,D.id AS did,O.zmena,O.dadresa,O.fadresa,
			O.platba_cena,O.dodani_cena,(O.platba_ncena+O.dodani_ncena) as ncena
			FROM objednavka O JOIN platby P ON platba=P.id JOIN dodani D ON O.dodani=D.id
			WHERE O.stav=%s AND datum >= %s AND datum < %s ORDER BY %sql",$stav,$dat1,$dat2,$sort)->fetchAll();

		foreach($result as $key=>$info)
		{
			if($info->dadresa)$result[$key]->dadresa = dibi::query("SELECT adresa FROM adresy WHERE id=%i", $info->dadresa)->fetchSingle();
			if($info->fadresa)$result[$key]->fadresa = dibi::query("SELECT adresa FROM adresy WHERE id=%i", $info->fadresa)->fetchSingle();
			$result[$key]->ncena += dibi::query("SELECT SUM(ncena*count) AS ncena FROM basket B JOIN variants V ON V.id=B.id_var WHERE B.id_obj=%i",$info->id)->fetchSingle();
			$result[$key]->items = dibi::query("SELECT B.id,P.id AS iid,V.id AS var_id,B.sleva,P.jmeno_$lang AS jmeno,V.jmeno_$lang AS varname,(B.price*(1+P.dph/100)) AS cena,count,id_user,kus_$lang AS kus,type FROM basket B JOIN variants V ON V.id=B.id_var JOIN products P ON P.id=V.vlastnik  WHERE B.id_obj=%i",$info->id)->fetchAll();
			foreach($result[$key]->items as $info1)
			{
				$uid = $info1->id_user;
				break;
			}
			if(isset($uid))$result[$key]->user = dibi::fetch("SELECT * FROM users WHERE id=%i",$uid);
			$result[$key]->invoice = dibi::query("SELECT id FROM faktura WHERE id_obj=%i",$info->id)->fetchSingle();
			$result[$key]->zisk = $result[$key]->cena - $result[$key]->ncena;
			$pole = explode(' ',$info->datum);
			$datum = explode('-',$pole[0]);
			$cas = explode(':',$pole[1]);
			$result[$key]->expedice = $info->expedice - round((mktime(date('H'),date('i'),date('s'),date('m'),date('d'),date('Y')) - mktime($cas[0],$cas[1],$cas[2],$datum[1],$datum[2],$datum[0]))/(3600*24));
		  if($result[$key]->expedice < 0)$result[$key]->expedice = 0;
		}

		return $result;
	}

	public function setStav($stav,$id)
	{
		dibi::query("UPDATE objednavka SET stav=%s WHERE id=%i",$stav,$id);

		if($stav == 'odeslano' || $stav == 'pripraveno')
		{
			// Odecteni zbozi ze skladu
			$result = dibi::query("SELECT id_var,count FROM basket WHERE id_obj=%i",$id)->fetchPairs('id_var','count');
			foreach($result as $var=>$count)
			{
				dibi::query("UPDATE variants SET sklad=sklad-%i WHERE id=%i AND sklad > %i",$count,$var,$count);
			}

			// Poslani emailu zakaznikovi
			$this->getInstanceOf('MailModel')->setMail($id,$stav);
		}
	}

	public function getPossStav($id)
	{
		return dibi::fetch("SELECT D.stav FROM objednavka O JOIN dodani D ON O.dodani=D.id WHERE O.id=%i",$id)->stav;
	}

	public function storno($id)
	{
		$result = dibi::query("SELECT count,id_var FROM basket WHERE sklad='ano' AND id_obj=%i",$id);
		foreach($result as $info)
		{
			dibi::query("UPDATE variants SET sklad=sklad+%i WHERE id=%i",$info->count,$info->id_var);
		}
		dibi::query("DELETE FROM basket WHERE id_obj=%i",$id);
		dibi::query("DELETE FROM objednavka WHERE id=%i",$id);
	}

	/**
	 * Vrati leta za ktera byly ucineny objednavky
	 *
	 * @return array Asociovane pole se seznamem roku
	 */
	public function getYears()
	{
		$ret = array();
		$datum = dibi::query("SELECT datum FROM objednavka ORDER BY datum LIMIT 1")->fetchSingle();

		if($datum)$year = NTemplateHelpers::date($datum, '%Y');
		else $year = date('Y');

		$max = date('Y');
		for($i = $year; $i<($max+1); $i++)
		{
			$ret[$i] = $i;
		}
		krsort($ret);
		return $ret;
	}

	public function getUserOrders($lang,$user)
	{
		$result1 = dibi::query("SELECT O.id,datum,O.pozn,O.cena,expedice,O.stav,D.jmeno_$lang AS dodani,P.jmeno_$lang AS platba,zdarma_od,P.cena AS pcena FROM objednavka O JOIN basket B ON B.id_obj=O.id JOIN platby P ON P.id=O.platba JOIN dodani D ON D.id=O.dodani WHERE B.id_user=%i GROUP BY B.id_obj",$user->getIdentity()->data['id'])->fetchAll();
		foreach($result1 as $key=>$info)
		{
			$result1[$key]->items = dibi::query("SELECT P.jmeno_$lang AS product,V.jmeno_$lang AS varianta,P.link_$lang AS link,(V.cena*(1+P.dph/100)) AS cena,count,V.kus_$lang AS kus FROM basket B JOIN variants V ON B.id_var=V.id JOIN products P ON V.vlastnik=P.id WHERE B.id_obj=%i",$info->id)->fetchAll();
			$pole = explode(' ',$info->datum);
			$datum = explode('-',$pole[0]);
			$cas = explode(':',$pole[1]);
			$result1[$key]->expedice = $info->expedice - round((mktime(date('H'),date('i'),date('s'),date('m'),date('d'),date('Y')) - mktime($cas[0],$cas[1],$cas[2],$datum[1],$datum[2],$datum[0]))/(3600*24));
		  if($result1[$key]->expedice < 0)$result1[$key]->expedice = 0;
		}
		return $result1;
	}

	public function getInvoice($order,$lang, $presenter)
	{
		$items = array();

		$res = dibi::fetch("SELECT mena,kurz,dodani,platba,dodani_cena,platba_cena,fadresa FROM objednavka WHERE id=%i",$order);
		if($res->fadresa)$addr = dibi::query("SELECT adresa FROM adresy WHERE id=%i", $res->fadresa)->fetchSingle();
		else $addr = NULL;

		$kurz = $res->kurz;
		$mena = $res->mena;
		$dodani = $res->dodani;
		$platba = $res->platba;
		$dodaniCena = $res->dodani_cena;
		$platbaCena = $res->platba_cena;

		$cnb = new Cnb($this->context->parameters['tempDir']);
		$money = $cnb->getMoney($mena);

		$res = dibi::fetch("SELECT id,datum FROM faktura WHERE id_obj=%i",$order);
		if(isset($res->id))
		{
			$id = $res->id;
			$datum = new DateTime($res->datum);
		}
		else{
			dibi::query("INSERT INTO faktura",array('id_obj'=>$order));
			$id = dibi::insertId();
			$datum = new DateTime();
		}

		$cena = 0;
		$result = dibi::query("SELECT IF(U.firma='',CONCAT(U.jmeno,' ',.U.prijmeni),U.firma) AS subjekt,U.ulice,U.mesto,U.psc,U.stat,V.jmeno_$lang AS varianta,V.cena,P.id,P.jmeno_$lang AS produkt,B.count FROM users U JOIN basket B ON B.id_user=U.id JOIN variants V ON B.id_var=V.id JOIN products P ON V.vlastnik=P.id WHERE B.id_obj=%i",$order)->fetchAll();
		foreach($result as $info)
		{
			$res2 = dibi::fetch("SELECT tax FROM tax WHERE %and",array('product'=>$info->id,'stat'=>$info->stat));
			if(isset($res2->tax))$tax = 1 + ($res2->tax/100);
			else $tax = 1;
			$items[] = new MyInvoiceItem($info->produkt.' '.$info->varianta, $info->count, $info->cena*$kurz*$tax, 1.00, TRUE);
			$cena += $info->count*$info->cena;
			$result1 = clone($info);
		}

		// Pripocitani ceny dopravy a platby
		if($dodaniCena)
		{
			$items[] = new MyInvoiceItem('Cena za dopravu a finanční transakci', 1, ($dodaniCena+$platbaCena)*$kurz*$tax, 1.00, TRUE);
		}

		$invoice = new InvoiceControl($id, 'Faktura - daňový doklad č.');
		$invoice->setParent($presenter);
		$invoice->mena = $money['symbol'];
		$invoice->setVariableSymbol($order);
		$invoice->setDateOfIssuance($datum);
		$invoice->setDateOfVatRevenueRecognition($datum);
		$datum2 = clone($datum);
		$datum2->modify('+14 days');
		$invoice->setExpirationDate($datum2);

		$supplier = new MyInvoiceParticipant('Jan Adamčík', 'Ptice', '10', 'okres Praha-západ', '252 18', '87644819', '', '2700111675/2010');
		$customer = new MyInvoiceParticipant($result1->subjekt, $result1->ulice, '', $result1->mesto, $result1->psc, '', '', '',$addr);
		$invoice->setSupplier($supplier);
		$invoice->setCustomer($customer);

		$invoice->addItems($items);

		include_once($this->context->parameters['libDir'] . '/MPDF57/mpdf.php');
		$mpdf = new mPDF('iso-8859-2');
		@$invoice->exportToPdf($mpdf);
	}

	public function setPaymentFromBank($data)
	{
		$result = dibi::fetch("SELECT stav,cena FROM objednavka WHERE id=%i",$data['vs']);
		if(isset($result->stav))
		{
			if($result->stav == 'odeslano' && $data['castka'] == $result->cena)dibi::query("UPDATE objednavka SET stav='vyrizeno' WHERE id=%i",$data['vs']);
			$this->getInstanceOf('MailModel')->sendZaplaceno($data['vs'],$data['castka'],$result->cena);
			dibi::query("INSERT INTO faktura",array('id_obj' => $data['vs']));
		}else return false;
		dibi::query("UPDATE objednavka SET zaplaceno=zaplaceno+%f WHERE id=%i",$data['castka'],$data['vs']);
		return true;
	}

	public function mergeOrders($id,$id2)
	{
		$res = dibi::query("SELECT id_user FROM basket WHERE id_obj IN (%i,%i) GROUP BY id_user",$id,$id2)->fetchAll();
		if(count($res) != 1 || $id == $id2)throw new InvalidArgumentException('Tyto objednavky nelze sloucit.');
		else{
			dibi::query("UPDATE basket SET id_obj=%i WHERE id_obj=%i",$id,$id2);
			dibi::query("DELETE FROM objednavka WHERE id=%i",$id2);
		}
	}

	public function getItemsInOrder($id,$lang)
	{
		return dibi::query("SELECT B.id,P.jmeno_$lang AS jmeno,V.jmeno_$lang AS varname,count,kus_$lang AS kus FROM basket B JOIN variants V ON V.id=B.id_var JOIN products P ON P.id=V.vlastnik  WHERE B.id_obj=%i",$id)->fetchAll();
	}

	/**
	 * Vytvori objednavku z produktu, ktere jsou v jine objednavce tim, ze je z teto objednavky odebere.
	 *
	 * @param array $data Pole s indexy order - cislo objednavky a check_$id kde $id je ID polozky v kosiku
	 * @return int ID nove vytvorene objednavky
	 */
	public function createOrder($data)
	{
		$result = dibi::fetch("SELECT * FROM objednavka WHERE id=%i",$data['order']);
		unset($result['id']);
		dibi::query("INSERT INTO objednavka",$result);
		$id = dibi::getInsertId();
		unset($data['order']);
		foreach($data as $key=>$value)
		{
			$check = explode('_',$key);
			if($value)dibi::query("UPDATE basket SET id_obj=%i WHERE id=%i",$id,$check[1]);
		}

		dibi::query('UPDATE objednavka SET cena=%f+dodani_cena+platba_cena WHERE id=%i',$this->getPriceOfItemsInOrder($id), $id);

		return $id;
	}

	public function setSleva($id,$sleva)
	{
		$res = dibi::fetch("SELECT id_obj,count,id_var,(V.cena*(1+P.dph/100)) AS cena,B.sleva FROM basket B JOIN variants V ON B.id_var=V.id JOIN products P ON V.vlastnik=P.id WHERE B.id=%i",$id);
		$cena1 = $res->cena*$res->count*(1-$res->sleva/100);
		$cena2 = $res->cena*$res->count*(1-$sleva/100);
		dibi::query("UPDATE objednavka SET cena=cena-%i+%i WHERE id=%i",$cena1,$cena2,$res->id_obj);
		dibi::query("UPDATE basket SET sleva=%i WHERE id=%i",$sleva,$id);
	}

	public function setCount($id,$count)
	{
		$res = dibi::fetch("SELECT id_obj,count,id_var,(V.cena*(1+P.dph/100)) AS cena,B.sleva FROM basket B JOIN variants V ON B.id_var=V.id JOIN products P ON V.vlastnik=P.id WHERE B.id=%i",$id);
		$cena1 = $res->cena*$res->count*(1-$res->sleva/100);
		$cena2 = $res->cena*$count*(1-$res->sleva/100);
		dibi::query("UPDATE objednavka SET cena=cena-%i+%i WHERE id=%i",$cena1,$cena2,$res->id_obj);
		dibi::query("UPDATE basket SET count=%i WHERE id=%i",$count,$id);
	}


	/**
	 * Vrati cenu polozek v objednavce
	 *
	 * @param int $orderId ID objednavky
	 */
	public function getPriceOfItemsInOrder($orderId)
	{
		return dibi::query("SELECT SUM((B.price*(1+P.dph/100)*(1-B.sleva/100))*B.count) FROM basket B JOIN variants V ON B.id_var=V.id JOIN products P ON V.vlastnik=P.id WHERE B.id_obj=%i",$orderId)->fetchSingle();
	}

	public function deleteItem($id)
	{
		$res = dibi::fetch("SELECT id_obj,count,id_var,(V.cena*(1+P.dph/100)) AS cena,B.sleva,B.sklad FROM basket B JOIN variants V ON B.id_var=V.id JOIN products P ON V.vlastnik=P.id WHERE B.id=%i",$id);
		$cena1 = $res->cena*$res->count*(1-$res->sleva/100);
		dibi::query("UPDATE objednavka SET cena=cena-%i WHERE id=%i",$cena1,$res->id_obj);
		if($res->sklad)dibi::query("UPDATE variants SET sklad=sklad+%i WHERE id=%i",$res->sklad,$res->id_var);
		dibi::query("DELETE FROM basket WHERE id=%i",$id);
	}

	public function setPrice($bid,$price)
	{
		$res = dibi::fetch("SELECT id_var,id_obj,dodani,platba FROM basket B JOIN objednavka O ON O.id=B.id_obj WHERE B.id=%i",$bid);
		dibi::query("UPDATE variants SET cena=%f WHERE id=%i",$price,$res->id_var);
		$cena = dibi::fetch("SELECT SUM(V.cena*(1-B.sleva/100)*(1+P.dph/100)*B.count) AS cena FROM basket B JOIN variants V ON B.id_var=V.id JOIN products P ON V.vlastnik=P.id WHERE B.id_obj=%i",$res->id_obj)->cena;

		$model = $this->getInstanceOf('PaymentModel');
		$dodani = $model->getDodani($res->dodani);
		$platba = $model->getPayment($res->platba);

		$cena = $cena + $dodani->cena + $platba->cena;

		dibi::query("UPDATE objednavka SET cena=%i WHERE id=%i",$cena,$res->id_obj);
	}

	public function setPaymentPrice()
	{
		$update = array();
		$result = dibi::query("SELECT id,dodani,platba,cena FROM objednavka WHERE dodani_cena IS NULL OR platba_cena IS NULL")->fetchAll();
		foreach($result as $info)
		{
			$res = dibi::fetch("SELECT cena,zdarma_od FROM dodani WHERE id=%i", $info->dodani);
			if($res->zdarma_od < $info->cena)$update['dodani_cena'] = 0;
			else $update['dodani_cena'] = $res->cena;
			$update['platba_cena'] = dibi::query("SELECT cena FROM platby WHERE id=%i", $info->platba)->fetchSingle();
			dibi::query("UPDATE objednavka SET ",$update,"WHERE id=%i",$info->id);
		}
	}

	public function getOrder($id)
	{
		return dibi::query("SELECT O.id,O.cena,jmeno,prijmeni,ulice,mesto,psc FROM basket B JOIN objednavka O ON B.id_obj=O.id JOIN users U ON B.id_user=U.id WHERE O.id=%i",$id)->fetch();
	}


	/**
	 * Ulozi novou adresu do DB
	 *
	 * @param string $adresa Adresa
	 * @return int ID adresy v DB
	 */
	private function setNewAddress($adresa)
	{
		$userId = $this->context->user->getIdentity()->data['id'];
		$id = dibi::query("SELECT id FROM adresy WHERE user_id=%i AND adresa=%s", $userId, $adresa)->fetchSingle();
		if($id == NULL)
		{
			dibi::query("INSERT INTO adresy", array('user_id' => $userId, 'adresa' => $adresa));
			$id = dibi::getInsertId();
		}
		return $id;
	}
}
