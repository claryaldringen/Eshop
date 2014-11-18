<?php

class ProductModel extends BaseModel{

	public function setData($id,NFormNControl $data)
	{
		$update[$data->getName()] = $data->getValue();
		if($data instanceof  NTextInput  && $update[$data->getName()] == '')$update[$data->getName()] = '-';
		if($data instanceof  NCheckbox){
			$result = dibi::fetch("SELECT [".$data->getName()."] FROM products WHERE id=%i",$id);
			if($result[$data->getName()])$update[$data->getName()] = 0;
			else $update[$data->getName()] = 1;
		}
		foreach($this->getLanguages() as $info)
		{
			$update['link_'.$info->zkratka] = NString::webalize($update['jmeno_'.$info->zkratka]);
		}
		return dibi::query("UPDATE products SET ",$update," WHERE id=%i",$id);
	}

	public function setDataProp($id,NFormNControl $data)
	{
		$update[$data->getName()] = $data->getValue();
		if($data instanceof  NTextInput  && $update[$data->getName()] == '')$update[$data->getName()] = '-';
		return dibi::query("UPDATE properties SET ",$update," WHERE id=%i",$id);
	}

	public function setDataVar($id,NFormNControl $data)
	{
		$update[$data->getName()] = $data->getValue();
		if($data instanceof  NTextInput  && $update[$data->getName()] == '')$update[$data->getName()] = '-';
		if($data->getName() == 'cena')$this->setSleva($id,$data->getValue());
		return dibi::query("UPDATE variants SET ",$update," WHERE id=%i",$id);
	}

	public function setNewLinesProp($count,$owner)
	{
		foreach($this->getLanguages() as $info)
		{
			$insert['jmeno_'.$info->zkratka] = '-';
			$insert['prop_'.$info->zkratka] = '-';
		}
		$insert['vlastnik'] = $owner;
		for($i=0;$i<$count;$i++)dibi::query("INSERT INTO properties",$insert);
	}

	public function setNewLinesVar($count,$owner)
	{
		foreach($this->getLanguages() as $info)
		{
			$insert['jmeno_'.$info->zkratka] = '-';
			$insert['kus_'.$info->zkratka] = '-';
		}
		$insert['vlastnik'] = $owner;
		for($i=0;$i<$count;$i++)dibi::query("INSERT INTO variants",$insert);
	}

	public function setNewLines($count)
	{
		foreach($this->getLanguages() as $info)
		{
			$insert['jmeno_'.$info->zkratka] = '-';
		}
		for($i=0;$i<$count;$i++)dibi::query("INSERT INTO products",$insert);
	}

	public function deleteItem($id)
	{
		return dibi::query("DELETE FROM products WHERE id=%i",$id);
	}

	public function deleteItemProp($id)
	{
		return dibi::query("DELETE FROM properties WHERE id=%i",$id);
	}

	public function deleteItemVar($id)
	{
		return dibi::query("UPDATE variants SET status='del' WHERE id=%i",$id);
	}

	public function getLanguages()
	{
		return dibi::query("SELECT zkratka FROM languages")->fetchAll();
	}

	public function getOwners()
	{
		$result = dibi::fetch("SELECT zkratka FROM languages");
		$result = dibi::query("SELECT id,jmeno_".$result->zkratka." AS jmeno FROM categories ORDER BY id DESC");
		return $result->fetchPairs('id','jmeno');
	}

	public function getObsah($id,$lang)
	{
		$result = dibi::fetch("SELECT popis_".$lang." AS obsah FROM products WHERE id=%i",$id);
		return $result->obsah;
	}

	private function setSleva($id,$cena)
	{
		$result = dibi::fetch("SELECT cena FROM pricelog ORDER BY datum DESC LIMIT 1");
		if(isset($result->cena))$sleva = 100-(($cena/$result->cena)*100);
		else $sleva = 0;
		dibi::query("INSERT INTO pricelog ",array('id_var'=>$id,'cena'=>$cena));
		dibi::query("UPDATE variants SET sleva=%i WHERE id=%i",$sleva,$id);
	}

	public function getProducts($owner,$offset,$limit,$lang,$sort = 'P.sort')
	{
		$return = array();
		$model = $this->getInstanceOf('KategorieModel');
		if(!isset($sort))$sort = 'P.sort';


		$ids = $model->getCategoriesRecursive($owner,$lang);
		if(!$ids)$ids = $owner;
		else $ids = $owner.','.$ids;

		// Urceni typu slozky - normal/collection
		if($owner)$type = dibi::fetch("SELECT type FROM categories WHERE id=%i",$owner)->type;
		else $type = 'normal';

		// Vytvoreni dotazu dle typu slozky
		if($type == 'normal')$sqlPart = "owner IN ($ids)";
		else
		{
			$sqlPart = "P.id IN (SELECT id_prod FROM collections WHERE id_coll=$owner)";
		}

		$result = dibi::query("
			SELECT P.id,link_$lang AS link,P.jmeno_$lang AS jmeno,popis_$lang AS popis,dph,(V.cena*(1+(dph/100))) AS scena
			FROM products P JOIN variants V ON V.vlastnik=P.id
			WHERE %sql AND P.status='ok' AND show_$lang=1 GROUP BY id ORDER BY P.id DESC %ofs %lmt",
		$sqlPart, $offset, $limit);

		foreach($result as $info)
		{
			$object = clone($info);
			$result2 = dibi::query("SELECT id,cena,sklad,sleva FROM variants WHERE vlastnik=%i AND status='ok' ORDER BY cena",$info->id);
			$info2 = $result2->fetch();
			$object->image = dibi::query("SELECT id FROM images WHERE vlastnik=%i ORDER BY sort,id",$info->id)->fetchSingle();
			$object->pocetVar = count($result2);
			$object->sklad = 0;
			$object->sleva = 0;
			if($this->context->params['frontend']['catshow'] == 'properties')
			{
				$object->properties = dibi::query("SELECT id,jmeno_$lang AS jmeno,prop_$lang AS prop FROM properties WHERE vlastnik=%i", $info->id);
			}
			if($object->pocetVar == 1)
			{
				$object->varianta = $info2->id;
				$object->sklad = $info2->sklad;
				$object->sleva = $info2->sleva;
			}else{
				foreach($result2 as $info3)
				{
					// Zjisteni jestli jsou nejake polozky skladem
					if($info3->sklad)$object->sklad = $info3->sklad;
					if(isset($info3->sleva) && $object->sleva < $info3->sleva)$object->sleva = $info3->sleva;
				}
			}

			if(isset($info) && isset($info2->cena))
			{
				if($info2->sleva)$object->cenastara = ceil($info2->cena*(1+$info->dph/100));
				$object->cena = ceil($info2->cena*(1-$info2->sleva/100)*(1+$info->dph/100));
			}
			else $object->cena = 0;
			$return[] = $object;
		}
		return $return;
	}

	public function getProductCount($owner,$lang,$type = 'normal')
	{
		if($owner)$type = dibi::fetch("SELECT type FROM categories WHERE id=%i",$owner)->type;
		else $type = 'normal';

		if($type == 'normal')
		{
			$model = $this->getInstanceOf('KategorieModel');
			$ids = $model->getCategoriesRecursive($owner,$lang);
			if(!$ids)$ids = $owner;
			return dibi::query("SELECT COUNT(*) FROM products WHERE owner IN (%sql) AND status='ok'",$ids)->fetchSingle();
		}else{
			return dibi::query("SELECT COUNT(*) FROM collections WHERE id_coll=%i", $owner)->fetchSingle();
		}
	}

	public function getProductsByIds($ids,$lang)
	{
		$return = array();
		$model = $this->getInstanceOf('KategorieModel');
		$ids = implode(',',$ids);
		$result = dibi::query("SELECT P.id,P.link_$lang AS link,P.jmeno_$lang AS jmeno,P.popis_$lang AS popis,I.id AS image,P.dph FROM products P JOIN images I ON P.id=I.vlastnik WHERE P.id IN ($ids) AND status='ok' AND show_$lang=1 GROUP BY P.id ORDER BY jmeno,I.sort,I.id");
		foreach($result as $info)
		{
			$object = new stdClass();
			$object = clone($info);
			$result2 = dibi::query("SELECT id,cena,sklad,sleva FROM variants WHERE vlastnik=%i AND status='ok' ORDER BY cena",$info->id);
			$info2 = $result2->fetch();
			$object->pocetVar = count($result2);
			$object->sklad = 0;
			if($object->pocetVar == 1)
			{
				$object->varianta = $info2->id;
				$object->sklad = $info2->sklad;
				$object->sleva = $info2->sleva;
			}else{
				foreach($result2 as $info3)
				{
					if($info3->sklad)
					{
						$object->sklad = $info3->sklad;
						break;
					}
				}
			}
			if(isset($info) && isset($info2->cena))$object->cena = ceil($info2->cena*(1+$info->dph/100));
			else $object->cena = 0;
			$return[] = $object;
		}
		return $return;
	}

	public function getAdminProducts($owner,$lang,$status='ok', $sort = 'sort')
	{
		$result = array();

		// Urceni typu slozky - normal/collection
		if($owner)$type = dibi::fetch("SELECT type FROM categories WHERE id=%i",$owner)->type;
		else $type = 'normal';

		// Vytvoreni dotazu dle typu slozky
		if($status == 'ok')
		{
			if($type == 'normal')$result = dibi::query("SELECT id,jmeno_$lang AS jmeno FROM products WHERE owner=%i AND show_$lang=1 AND status=%s ORDER BY %sql",$owner,$status,$sort)->fetchAll();
			else $result = dibi::query("SELECT id,jmeno_$lang AS jmeno FROM products P JOIN collections C ON C.id_prod=P.id WHERE show_$lang=1 AND C.id_coll=%i AND status=%s ORDER BY C.sort",$owner,$status)->fetchAll();
		}else{
			if($type == 'normal')$result = dibi::query("SELECT id,jmeno_$lang AS jmeno FROM products WHERE  status=%s ORDER BY sort",$status)->fetchAll();
		}

		foreach($result as $key=>$info)
		{
			$res = dibi::fetch("SELECT id FROM images WHERE vlastnik=%i AND typ='produkt' ORDER BY sort",$info->id);
			if(isset($res->id))$info->image = $res->id;
			else $info->image = 0;
			$result[$key] = $info;
		}
		return $result;
	}

	/**
	 * Vlozi variantu produktu do kosiku.
	 *
	 * @param int $pocet Pocet kusu od dane varianty vlozeny do kosiku
	 * @param int $id ID varianty
	 * @param int $user ID uzivatele
	 */
	public function addToBasket($pocet,$id,$user)
	{
		$result = dibi::fetch("SELECT id FROM basket WHERE id_user=%i AND id_var=%i AND id_obj=0",$user,$id);
		if(isset($result->id))dibi::query("UPDATE basket SET count=count+%i WHERE id=%i",$pocet,$result->id);
		else
		{
			dibi::query("INSERT INTO basket",array('id_user'=>$user,'id_var'=>$id,'count'=>$pocet));
			return dibi::getInsertId();
		}
	}

	public function getBasket($user,$obj=0)
	{
		$result = dibi::fetch("SELECT SUM(count) AS pocet,SUM(V.cena*B.count*(1+P.dph/100)*(1-V.sleva/100)) AS cena FROM basket B JOIN variants V ON V.id=B.id_var JOIN products P ON P.id=V.vlastnik WHERE B.id_user=%i AND B.id_obj=%i",$user,$obj);
		return array('pocet'=>(int)$result->pocet,'cena'=>ceil($result->cena));
	}

	public function getBasketDetail($user,$lang,$obj=0)
	{
		$result = dibi::query("SELECT B.id,P.jmeno_$lang AS produkt,V.id AS id_var,V.type,V.jmeno_$lang AS varianta,B.count AS pocet,P.dph,CEIL(V.cena*B.count*(1+P.dph/100)*(1-V.sleva/100)) AS cena FROM basket B JOIN variants V ON V.id=B.id_var JOIN products P ON P.id=V.vlastnik WHERE B.id_user=%i AND B.id_obj=%i",$user,$obj);
		return $result->fetchAll();
	}

	public function setBasket($user,$values)
	{
		foreach($values as $key=>$value)
		{
			$id = explode('_',$key);
			dibi::query("UPDATE basket SET count=%i WHERE id=%i",$value,$id[1]);
		}
		dibi::query("DELETE FROM basket WHERE id_user=%i AND id_obj=0 AND count=0",$user);
	}

	public function deleteBasket($user)
	{
		dibi::query("DELETE FROM basket WHERE id_user=%i AND id_obj=0",$user);
	}

	public function getProduct($id,$lang)
	{
		$result = dibi::fetch("SELECT id,jmeno_$lang AS jmeno,popis_$lang AS popis,dodani,dph FROM products WHERE id=%i",$id);
		if(!isset($result->id))throw new NBadRequestException('Produkt nenalezen');

		$result->properties	= dibi::query("SELECT jmeno_$lang AS jmeno,prop_$lang AS prop FROM properties WHERE vlastnik=%i",$id)->fetchPairs('jmeno','prop');
		$result->variants	= dibi::query("SELECT id,jmeno_$lang AS jmeno,kus_$lang AS kus,cena,sklad,sleva FROM variants WHERE status='ok' AND vlastnik=%i",$id)->fetchAll();
		$result->images = dibi::query("SELECT id,popis_$lang AS popis FROM images WHERE typ='produkt' AND vlastnik=%i ORDER BY sort",$id)->fetchPairs('id','popis');
		return $result;
	}

	public function getProductFromPath($cpath,$path,$lang)
	{
		$model = $this->getInstanceOf('KategorieModel');
		$cat = $model->getIdFromPath($cpath,$lang);
		$res = dibi::fetch("SELECT id FROM products WHERE link_$lang=%s AND owner=%i",$path,$cat);
		if(isset($res->id))return $res->id;
	}

	public function move($co,$kam)
	{
		dibi::query("UPDATE products SET owner=%i WHERE id IN ('".implode("','",$co)."')",$kam);
	}

	public function setName($name,$id,$lang)
	{
		return dibi::query("UPDATE products SET jmeno_".$lang."=%s,link_".$lang."=%s WHERE id=%i",$name,NStrings::webalize($name),$id);
	}

	/**
	 * Vytvori novy produkt
	 *
	 * @param int $owner ID kategorie
	 * @return int ID produktu
	 */
	public function newProd($owner)
	{
		$langs = $this->getLanguages();
		foreach($langs as $lang)
		{
			$pole['jmeno_'.$lang->zkratka] = 'Nový produkt';
			$pole['link_'.$lang->zkratka] = md5(microtime());
		}
		$pole['owner'] = $owner;
		dibi::query("INSERT INTO products",$pole);
		return dibi::getInsertId();
	}

	/**
	 * Odstrani produkt do kose
	 *
	 * @param array $co ID odstranovanych produktu
	 * @param int $owner ID slozky
	 * @param string $status status po odstraneni
	 */
	public function delete($co,$owner = 0,$status = 'del')
	{
		// Urceni typu slozky - normal/collection
		if($owner)$type = dibi::fetch("SELECT type FROM categories WHERE id=%i",$owner)->type;
		else $type = 'normal';

		if($type == 'normal')dibi::query("UPDATE products SET status=%s,owner=0 WHERE id IN %in",$status,$co);
		else dibi::query("DELETE FROM collections WHERE id_prod IN %in AND id_coll=%i",$co,$owner);
	}

	public function setImage($item,$file,$typ='produkt')
	{
		if($file instanceof NHttpUploadedFile)$image = $file->toImage();
		else $image = NImage::fromFile($file);

		dibi::query("INSERT INTO images",array('vlastnik'=>$item,'typ'=>$typ));
		$id = dibi::getInsertId();

		$image->resize($this->context->params['image']['largewidth'], $this->context->params['image']['largeheight']);
		$image->save($this->context->params['wwwDir'] . '/images/uploaded/large'.$id.'.jpg');

		$image->resize($this->context->params['image']['mediumwidth'],$this->context->params['image']['mediumheight']);
		$image->save($this->context->params['wwwDir'] . '/images/uploaded/medium'.$id.'.jpg');

		$image->resize($this->context->params['image']['miniwidth'],$this->context->params['image']['miniheight']);
		$image->save($this->context->params['wwwDir'] . '/images/uploaded/mini'.$id.'.jpg');

		return $id;
	}

	public function getImages($id,$lang,$typ = 'produkt')
	{
		return dibi::query("SELECT id,popis_$lang AS popis FROM images WHERE vlastnik=%i AND typ=%s ORDER BY sort",$id,$typ)->fetchPairs('id','popis');
	}

	public function setSort($post)
	{
		$data = explode('&',$post);
		foreach($data as $key=>$val)
		{
			$id = explode('=',$val);
			dibi::query("UPDATE images SET sort=%i WHERE id=%i",$key,$id[1]);
		}
	}

	public function setItemSort($post)
	{
		$data = explode('&',$post);
		foreach($data as $key=>$val)
		{
			$id = explode('=',$val);
			dibi::query("UPDATE products SET sort=%i WHERE id=%i",$key,$id[1]);
		}
	}

	public function setCatSort($post)
	{
		$data = explode('&',$post);
		foreach($data as $key=>$val)
		{
			$id = explode('=',$val);
			dibi::query("UPDATE categories SET sort=%i WHERE id=%i",$key,$id[1]);
		}
	}

	public function deleteImage($id)
	{
		dibi::query("DELETE FROM images WHERE id=%i",$id);
		unlink($this->context->params['wwwDir'] . '/images/uploaded/mini'.$id.'.jpg');
		unlink($this->context->params['wwwDir'] . '/images/uploaded/medium'.$id.'.jpg');
		unlink($this->context->params['wwwDir'] . '/images/uploaded/large'.$id.'.jpg');
	}

	public function renameImage($id,$name,$lang)
	{
		dibi::query("UPDATE images SET popis_$lang=%s WHERE id=%i",$name,$id);
	}

	public function setPopis($popis,$id,$lang)
	{
		dibi::query("UPDATE products SET popis_$lang=%s WHERE id=%i",$popis,$id);
	}

	public function getPopis($id,$lang)
	{
		return dibi::fetch("SELECT popis_$lang AS popis FROM products WHERE id=%i",$id)->popis;
	}

	public function getProperties($vlastnik,$lang)
	{
		return dibi::query("SELECT id,jmeno_$lang AS jmeno,prop_$lang AS prop FROM properties WHERE vlastnik=%i ORDER BY id",$vlastnik)->fetchAll();
	}

	public function getVariants($vlastnik,$lang,$type='normal')
	{
		return dibi::query("SELECT id,jmeno_$lang AS jmeno,kus_$lang AS kus,cena,ncena,sklad FROM variants WHERE vlastnik=%i AND status='ok' AND type=%s ORDER BY id",$vlastnik,$type)->fetchAll();
	}

	public function addProp($vlastnik)
	{
		dibi::query("INSERT INTO properties",array('vlastnik'=>$vlastnik));
	}

	public function addVariant($vlastnik)
	{
		dibi::query("INSERT INTO variants",array('vlastnik'=>$vlastnik));
	}

	public function deleteEmptyProp($vlastnik)
	{
		$res = dibi::query("SELECT zkratka FROM languages");
		foreach($res as $info)
		{
			 $pole['jmeno_'.$info->zkratka] = '';
			 $pole['prop_'.$info->zkratka] = '';
		}
		//dibi::test("DELETE FROM properties WHERE %and",$pole);
	}

	public function deleteEmptyVar($vlastnik)
	{
		$res = dibi::query("SELECT zkratka FROM languages");
		foreach($res as $info)
		{
			 $pole['jmeno_'.$info->zkratka] = '';
			 $pole['kus_'.$info->zkratka] = '';
		}
		$pole['ncena'] = 0.00;
		$pole['cena'] = 0.00;
		//Jestlize se varianta nikde nepouziva, smazeme ji jinak nastavime status
		$toStatus = dibi::query("SELECT id_var FROM basket WHERE id_var IN (SELECT id FROM variants WHERE %and)", $pole)->fetchPairs(NULL,'id_var');
		if(!empty($toStatus))
		{
			dibi::query("UPDATE variants SET status='del' WHERE id IN %in",$toStatus);
		}
		dibi::query("DELETE FROM variants WHERE %and AND status='ok'",$pole);
	}

	public function setProp($pole,$id)
	{
		dibi::query("UPDATE properties SET",$pole," WHERE id=%i",$id);
	}

	public function getDph($id)
	{
		return dibi::fetch("SELECT dph FROM products WHERE id=%i",$id)->dph;
	}

	public function getDodani($id)
	{
		return dibi::fetch("SELECT dodani FROM products WHERE id=%i",$id)->dodani;
	}

	public function setDodani($id,$dodani)
	{
		dibi::query("UPDATE products SET dodani=%i WHERE id=%i",$dodani,$id);
	}

	public function setVariant($id,$data)
	{
		dibi::query("UPDATE variants SET ",$data," WHERE id=%i",$id);
	}

	public function newVariant($id,$data)
	{
		$data['vlastnik'] = $id;
		dibi::query("INSERT INTO variants",$data);
	}

	public function getSklad($owner,$lang,$filter = array(),$sort = 'P.id')
	{
		$zisk = new stdClass();
		$zisk->count = 0;

		if($owner == 'all')
		{
			$model = $this->getInstanceOf('KategorieModel');
			$owner = implode(',',array_keys($model->getAllCats($lang)));
		}
		$col = key($filter);

		$cols = implode(',A.',$this->getAdditionals('name'));
		if($cols)$cols = ',A.'.$cols;

		if(empty($filter))
		{
			$result = dibi::query("SELECT
				P.id,P.jmeno_$lang AS jmeno,P.dodani,P.dph,show_$lang AS [show],V.id AS vid,
				V.jmeno_$lang AS varname,V.cena,V.ncena,V.sklad,V.minsklad,V.hmotnost,V.sleva,V.kod %sql
				FROM products P JOIN variants V ON V.vlastnik=P.id LEFT JOIN addvals A ON A.id_prod=P.id
				WHERE P.status='ok' AND V.status='ok' AND V.type='normal' AND P.owner IN (%sql) ORDER BY %sql",
				$cols, $owner,$sort)->fetchAll();
		}
		else
		{
			$result = dibi::query("SELECT
				P.id,P.jmeno_$lang AS jmeno,P.dodani,P.dph,show_$lang AS [show],V.id AS vid,
				V.jmeno_$lang AS varname,V.cena,V.ncena,V.sklad,V.minsklad,V.hmotnost,V.sleva,V.kod %sql
				FROM products P JOIN variants V ON V.vlastnik=P.id LEFT JOIN addvals A ON A.id_prod=P.id
				WHERE P.status='ok' AND V.status='ok' AND V.type='normal' AND P.owner IN (%sql) AND %sql LIKE %s ORDER BY %sql",
				$cols,$owner,$col,$filter[$col],$sort)->fetchAll();
		}

		foreach($result as $key=>$info)
		{
			$info->cnc = $info->sklad*$info->ncena;
			$info->cpc = $info->sklad*$info->cena;
			$info->czisk = $info->cpc - $info->cnc;
			$info->zisk = $info->cena-$info->ncena;
			$zisk->ppzisk = 0;
			if($info->ncena > 0)
			{
				$info->pzisk = round(($info->cena/($info->ncena/100))-100,2);
				$zisk->count++;
				$zisk->ppzisk += $info->pzisk;
			}else $info->pzisk = 0;
			$zisk->czisk += $info->czisk;
			$zisk->sklad += $info->sklad;
			$result[$key] = $info;
		}
		if(isset($zisk->count) && isset($zisk->ppzisk) && $zisk->count)$zisk->ppzisk = round($zisk->ppzisk/$zisk->count,2);
		$result[] = $zisk;
		return $result;
	}

	public function getItems($owner,$lang,$order = 'sort')
	{
		return dibi::query("SELECT id,jmeno_$lang AS jmeno FROM products WHERE owner IN ($owner) AND show_$lang=1 ORDER BY %sql",$order)->fetchPairs('id','jmeno');
	}

	public function setDph($id,$dph)
	{
		dibi::query("UPDATE products SET dph=%i WHERE id=%i",$dph,$id);
	}

	public function getItemName($id,$lang)
	{
		return dibi::fetch("SELECT jmeno_$lang AS jmeno FROM products WHERE id=%i",$id)->jmeno;
	}

	public function setComplements($data,$id)
	{
		dibi::query("DELETE FROM comandsup WHERE id_prod=%i AND id_com IN (".implode(',',$data).") AND type='comp'",$id);
		foreach($data as $val)dibi::query("INSERT INTO comandsup",array('id_prod'=>$id,'id_com'=>$val,'type'=>'comp'));
	}

	public function setSuplements($data,$id)
	{
		dibi::query("DELETE FROM comandsup WHERE id_prod=%i AND id_com IN (".implode(',',$data).") AND type='supl'",$id);
		foreach($data as $val)dibi::query("INSERT INTO comandsup",array('id_prod'=>$id,'id_com'=>$val,'type'=>'supl'));
	}

	public function getComplements($id,$type,$lang)
	{
		return dibi::query("SELECT P.id,P.jmeno_$lang AS jmeno FROM comandsup C JOIN products P ON P.id = C.id_com WHERE type=%s AND id_prod=%i",$type,$id)->fetchPairs();
	}

	public function remFromComp($data,$typ,$id)
	{
		dibi::query("DELETE FROM comandsup WHERE id_prod=%i AND id_com IN (".implode(',',$data).") AND type=%s",$id,$typ);
	}

	public function getSpecials($id,$lang)
	{
		return dibi::query("SELECT id,name_$lang AS name FROM special WHERE id_prod=%i",$id)->fetchPairs('id','name');
	}

	public function setNewSpecial($name,$id,$lang)
	{
		dibi::query("INSERT INTO special",array('id_prod'=>$id,'name_'.$lang=>$name));
	}

	public function deleteSpecial($id)
	{
		dibi::query("DELETE FROM special2 WHERE id_spec=%i",$id);
		dibi::query("DELETE FROM special WHERE id=%i",$id);
	}

	public function setSpecial2($data)
	{
		dibi::query("UPDATE special SET typ=%i,[values]=%s WHERE id=%i",$data['typ'],$data['values'],$data['id_spec']);
		dibi::query("DELETE FROM special2 WHERE id_spec=%i",$data['id_spec']);
		if($data['filled'])dibi::query("INSERT INTO special2",array('id_spec'=>$data['id_spec'],'typ'=>1));
		if($data['number'] == 'float')dibi::query("INSERT INTO special2",array('id_spec'=>$data['id_spec'],'typ'=>2,'range1'=>$data['od'],'range2'=>$data['do']));
		if($data['number'] == 'integer')dibi::query("INSERT INTO special2",array('id_spec'=>$data['id_spec'],'typ'=>3,'range1'=>$data['od'],'range2'=>$data['do']));
	}

	public function getSpecial2($id)
	{
		$values = array('filled'=>0,'od'=>'','do'=>'','number'=>0);
		$result1 = dibi::fetch("SELECT typ,[values] FROM special WHERE id=%i",$id);
		$values['typ'] = $result1->typ;
		$values['values'] = $result1->values;
		$result2 = dibi::query("SELECT typ,range1,range2 FROM special2 WHERE id_spec=%i",$id);
		foreach($result2 as $info)
		{
			if($info->typ == 1)$values['filled'] = 1;
			if($info->typ == 2)$values['number'] = 'float';
			if($info->typ == 3)$values['number'] = 'integer';
			if($info->typ == 2 || $info->typ == 3)
			{
				$values['number'] = 'float';
				$values['od'] = $info->range1;
				$values['do'] = $info->range2;
			}
		}
		return $values;
	}

	public function setSpecPopis($popis,$id,$lang)
	{
		dibi::query("UPDATE products SET specpopis_$lang=%s WHERE id=%i",$popis,$id);
	}

	public function getSpecPopis($id,$lang)
	{
		return dibi::fetch("SELECT specpopis_$lang AS popis FROM products WHERE id=%i",$id)->popis;
	}

	public function setDefaultPrice($id,$cena)
	{
		dibi::query("UPDATE products SET cena=%i WHERE id=%i",$cena,$id);
	}

	public function getDefaultPrice($id)
	{
		return dibi::fetch("SELECT cena FROM products WHERE id=%i",$id)->cena;
	}

	public function setSpecConditions($text,$id,$lang)
	{
		dibi::query("DELETE FROM conditions WHERE id_prod=%i",$id);
		$el = array();
		$co = array('IF','/*','*/','//','=' ,'<==','>==','==>','==<','====','AND','OR');
		$cim = array('' ,'','','','==','<=' ,'>=' ,'>=' ,'<=' ,'==','&&' ,'||');
		$specials = $this->getSpecials($id,$lang);
		foreach($specials as $key=>$val)
		{
			$co[] = '"'.$val.'"';
			$cim[] = '$form[\''.$key.'\']';
			$el[(string)$key] = '';
		}
		$text = nl2br($text);
		$text = str_replace(array("\n","\r"),'',$text);
		$pole1 = explode('<br />',$text);
		foreach($pole1 as $row => $cond)
		{

			$pole2 = explode('THEN',$cond);
			$if = str_replace($co,$cim,$pole2[0]);
			if($this->securityTest($if,$cim))return array($row,6);
			if(isset($pole2[1]))$then = $pole2[1];
			else return array($row,2);

			$tt = $this->testThen($then);
			if($tt)return array($row,$tt);

			error_reporting(0);
			if($this->testConditions($if,$el))dibi::query("INSERT INTO conditions",array('id_prod'=>$id,'if'=>$if,'then'=>$then));
			else return array($row,1);
		}
	}

	private function testConditions($cond,$form)
	{
		return eval('if('.$cond.')return 1;else return 2;');
	}

	private function testThen($then)
	{
		$then = str_replace(' ','',$then);
		$max = strlen($then);
		for($i=0;$i<$max;$i++)
		{
			$char = substr($then,$i,1);
			if($i == 0 && !in_array($char,array('+','-')))return 3;
			elseif($i == ($max-1) && !in_array($char,array('0','1','2','3','4','5','6','7','8','9','%')))return 5;
			elseif($i !=0 && $i != ($max-1) && !in_array($char,array('0','1','2','3','4','5','6','7','8','9')))return 4;
		}

	}

	private function securityTest($cond,$form)
	{
		$cond = str_replace(array(' ',"\r","\n"),'',$cond);
		$co = array('(',')','==','<=' ,'>=' ,'>=' ,'<=' ,'==','&&' ,'||');
		$cond = str_replace($co,'@#$%',$cond);
		$pole = explode('@#$%',$cond);
		foreach($pole as $item)
		{
			if(!in_array($item,$form))
			{
				$char1 = substr($item,0,1);
				$char2 = substr($item,strlen($item)-1,1);

				if($char1 != '"' || $char2 != '"')return true;
			}
		}
	}

	public function getConditions($prodid,$lang)
	{
		$co = array();
		$cim = array();
		$radek = '';
		$result = dibi::query("SELECT id,name_$lang AS name FROM special WHERE id_prod=%i",$prodid)->fetchPairs('id','name');
		foreach($result as $key=>$val)
		{
			$co[] = '$form[\''.$key.'\']';
			$cim[] = '"'.$val.'"';
		}
		$result = dibi::query("SELECT id,[if],[then] FROM conditions WHERE id_prod=%i",$prodid)->fetchAll();
		foreach($result as $row)
		{
			$radek .= 'IF'.str_replace($co,$cim,$row->if).'THEN'.$row->then."\n";
		}
		return $radek;
	}

	public function getNewProducts($pocet,$lang)
	{
		$result = dibi::query("SELECT P.id,P.jmeno_$lang AS jmeno,(V.cena*(1+P.dph/100)) AS cena,(P.cena*(1+P.dph/100)) AS scena,P.link_$lang AS link FROM products P JOIN variants V ON P.id=V.vlastnik WHERE P.status='ok' AND V.status='ok' AND show_$lang=1 GROUP BY P.id ORDER BY P.datum DESC,V.cena LIMIT %i",$pocet)->fetchAll();
		foreach($result as $key=>$val)
		{
			$result[$key]->image = dibi::fetch("SELECT id FROM images WHERE vlastnik=%i ORDER BY sort,id LIMIT 1",$val->id)->id;
			$result[$key]->path = $this->getProductCPath($val->id,$lang);
		}
		return $result;
	}

	public function getProductCPath($id,$lang)
	{
		$id = dibi::fetch("SELECT owner FROM products WHERE id=%i",$id)->owner;
		$model = $this->getInstanceOf('KategorieModel');
		return substr($model->getPathFromId($id,$lang),0,-1);
	}

	public function search($word,$lang)
	{
		$word3 = NStrings::webalize($word);
		$word = htmlentities($word,ENT_NOQUOTES,'UTF-8');
		$word2 = str_replace('-',' ',$word3);
		$word3 = '%'.$word3.'%';
		$word4 = '%'.$word.'%';
		$results = array();
		$ids = array();
		$results[0] = dibi::query("SELECT id FROM products WHERE MATCH jmeno_$lang AGAINST (%s)",$word)->fetchAll();
		$results[1] = dibi::query("SELECT id FROM products WHERE MATCH jmeno_$lang AGAINST (%s)",$word2)->fetchAll();
		if(empty($results[0]) && empty($results[1]))$results[2] = dibi::query("SELECT id FROM products WHERE link_$lang LIKE %s",$word3)->fetchAll();
		if(empty($results[0]) && empty($results[1]))$results[] = dibi::query("SELECT id FROM products WHERE MATCH popis_$lang AGAINST (%s)",$word)->fetchAll();
		if(empty($results[0]) && empty($results[1]))$results[] = dibi::query("SELECT id FROM products WHERE MATCH popis_$lang AGAINST (%s)",$word2)->fetchAll();
		foreach($results as $result)
		{
			foreach($result as $info)$ids[] = $info->id;
		}
		if(empty($ids))
		{
			$results[] = dibi::query("SELECT id FROM products WHERE jmeno_$lang LIKE %s",$word4)->fetchAll();
			$results[] = dibi::query("SELECT id FROM products WHERE popis_$lang LIKE %s",$word4)->fetchAll();
			foreach($results as $result)
			{
				foreach($result as $info)$ids[] = $info->id;
			}
		}
		return array_unique($ids);
	}

	public function getAllProducts($lang)
	{
	  return dibi::query("SELECT id,jmeno_$lang AS jmeno FROM products WHERE status='ok'")->fetchPairs('id','jmeno');
	}

	public function getBestsellers($count,$lang)
	{
	  $result = dibi::query("SELECT P.id,P.jmeno_$lang AS jmeno,P.link_$lang AS link,(V.cena*(1+P.dph/100)) AS cena,(P.cena*(1+P.dph/100)) AS scena,COUNT(*) AS pocet FROM basket B JOIN variants V ON B.id_var=V.id JOIN products P ON V.vlastnik=P.id WHERE id_obj != 0 AND P.status='ok' GROUP BY P.id ORDER BY pocet DESC LIMIT %i",$count)->fetchAll();
		foreach($result as $key=>$val)
		{
			$result[$key]->image = dibi::fetch("SELECT id FROM images WHERE vlastnik=%i ORDER BY sort,id LIMIT 1",$val->id)->id;
			$result[$key]->path = $this->getProductCPath($val->id,$lang);
		}
		return $result;
	}

	public function getRecomended($count,$lang)
	{
	  $result = dibi::query("SELECT P.id,P.jmeno_$lang AS jmeno,P.link_$lang AS link,(V.cena*(1+P.dph/100)) AS cena,(P.cena*(1+P.dph/100)) AS scena FROM variants V JOIN products P ON V.vlastnik=P.id WHERE V.status='ok' AND P.id IN (SELECT id_prod FROM collections WHERE id_coll=54) GROUP BY P.id LIMIT %i",$count)->fetchAll();
		foreach($result as $key=>$val)
		{
			$result[$key]->image = dibi::fetch("SELECT id FROM images WHERE vlastnik=%i ORDER BY sort,id LIMIT 1",$val->id)->id;
			$result[$key]->path = $this->getProductCPath($val->id,$lang);
		}
		return $result;
	}

	public function getSuplements($id,$lang,$type)
	{
	  $result = dibi::query("SELECT P.id,P.jmeno_$lang AS jmeno,P.link_$lang AS link,(V.cena*(1+P.dph/100)) AS cena,(P.cena*(1+P.dph/100)) AS scena FROM variants V JOIN products P ON V.vlastnik=P.id WHERE V.status='ok' AND P.id IN (SELECT id_com FROM comandsup WHERE id_prod=%i AND type=%s) GROUP BY P.id",$id,$type)->fetchAll();
		foreach($result as $key=>$val)
		{
			$result[$key]->image = dibi::fetch("SELECT id FROM images WHERE vlastnik=%i ORDER BY sort,id LIMIT 1",$val->id)->id;
			$result[$key]->path = $this->getProductCPath($val->id,$lang);
		}
		return $result;
	}


	public function moveToCollection(array $co,$kam)
	{
		$res = dibi::query("SELECT id_prod FROM collections WHERE id_coll=%i",$kam)->fetchPairs('id_prod','id_prod');
		$pole = array_diff($co,$res);
		foreach($pole as $val)
		{
			dibi::query("INSERT INTO collections",array('id_prod'=>$val,'id_coll'=>$kam));
		}
	}

	public function getOtherItem($id,$type)
	{
			if($type == 'next')$res = dibi::fetch("SELECT id FROM products WHERE id > %i ORDER BY sort,id LIMIT 1",$id);
			else $res = dibi::fetch("SELECT id FROM products WHERE id < %i ORDER BY sort,id DESC LIMIT 1",$id);
			if(isset($res->id))return $res->id;
	}

	public function checkSklad($pole)
	{
		$result = dibi::query("SELECT P.jmeno_cs AS product,V.jmeno_cs AS variant,V.sklad,V.kus_cs AS kus FROM variants V JOIN products P ON P.id=V.vlastnik WHERE V.id IN %in AND sklad < minsklad",array_keys($pole))->fetchAll();
		if(count($result))
		{
			$this->getInstanceOf('MailModel')->outOfStock($result);
		}
	}

	public function getSpecialsOnFrontend($id,$lang)
	{
		$result = dibi::query("SELECT id,name_$lang AS name,typ,[values] FROM special WHERE id_prod=%i",$id)->fetchAll();
		foreach($result as $key=>$info)
		{
			$info->values = substr($info->values,1,strlen($info->values)-2);
			$info->values = explode('","',$info->values);
			$info->rules = dibi::query("SELECT typ,range1,range2 FROM special2 WHERE id_spec=%i",$info->id)->fetchAll();
			$result[$key] = $info;
		}
		return $result;
	}

	public function createSpecialVariant($data)
	{
		$variant = array('jmeno_cs'=>'dle specifikací zákazníka','vlastnik'=>$data['vlastnik'],'type'=>'special');
		dibi::query("INSERT INTO variants",$variant);
		$id = dibi::getInsertId();
		unset($data['vlastnik']);

		foreach($data as $spec=>$value)
		{
			$pole = explode('_',$spec);
			$res = dibi::fetch("SELECT name_cs AS name,typ,[values] FROM special WHERE id=%i",$pole[1]);
			if($res->typ == 3)
			{
				$pole = explode('","',substr($res->values,1,strlen($res->values)-2));
				$value = $pole[$value];
			}

			//zpracovani souboru
			if($res->typ == 5)
			{
				if(!empty($value->name))
					{
					if($value->isImage())
					{
						$this->setImage($id, $value, 'specialni');
						$value = '#image#';
					}elseif($value->getContentType() == 'application/zip'){
						$filename = md5(microtime());
						$value->move($this->context->params['tempDir'] . '/c-Nette.Uploaded/'.$filename.'.zip');
						$this->unpackZip($this->context->params['tempDir'] . '/c-Nette.Uploaded/', $filename,$id );
						$value = '#image#';
					}else throw new UnexpectedValueException('Soubor není obrázek ani *.zip.');
				}else $value = '#image#';
			}
			dibi::query("INSERT INTO specialvals",array('name_cs'=>$res->name,'value'=>$value,'id_var'=>$id));
		}

		return $id;
	}

	public function getSpecialFromOrder($id_var,$lang)
	{
		$ret = new stdClass();
		$ret->spec = array();
		$ret->images = array();

		$result = dibi::query("SELECT name_$lang AS name,value FROM specialvals WHERE id_var=%i",$id_var)->fetchAll();
		foreach($result as $info)
		{
			if($info->value == '#image#')$ret->images = dibi::query("SELECT id FROM images WHERE vlastnik=%i AND typ='specialni'",$id_var)->fetchPairs('id','id');
			else $ret->spec[$info->name] = $info->value;
		}

		return $ret;
	}

	public function copyItems($items)
	{
		$result = dibi::query("SELECT * FROM products WHERE id IN %in",$items)->fetchAll();
		foreach($result as $info)
		{
			foreach($this->getLanguages() as $lang)
			{
				$info['link_'.$lang->zkratka] = md5(time().$lang->zkratka);
			}

			$res1 = dibi::query("SELECT * FROM properties WHERE vlastnik=%i",$info->id)->fetchAll();
			$res2 = dibi::query("SELECT * FROM variants WHERE vlastnik=%i AND status='ok'",$info->id)->fetchAll();
			unset($info->id);
			dibi::query("INSERT INTO products",$info);
			$id = dibi::getInsertId();
			foreach($res1 as $info1)
			{
				unset($info1->id);
				$info1->vlastnik = $id;
				dibi::query("INSERT INTO properties",$info1);
			}
			foreach($res2 as $info2)
			{
				unset($info2->id);
				$info2->vlastnik = $id;
				dibi::query("INSERT INTO variants",$info2);
			}
		}
	}

	public function setAdditional($data)
	{
		$data['name'] = NString::webalize($data['nazev']);
		dibi::query("INSERT INTO additional",$data);
		dibi::query("ALTER TABLE addvals ADD %s VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL",$data['nazev']);
	}

	public function getAdditionals($type)
	{
		return dibi::query("SELECT %sql AS val FROM additional", $type)->fetchPairs(NULL, 'val');
	}

	public function getAdditionals2()
	{
		return dibi::query("SELECT * FROM additional")->fetchAll();
	}

	public function setAdditionalVal($data,$pid)
	{
		$data2 = $data;
		$data2['id_prod'] = $pid;
		dibi::query("INSERT INTO addvals",$data2," ON DUPLICATE KEY UPDATE %a",$data);
	}

	public function getProductsInSleva($lang)
	{
		$return = array();

		// Vytvoreni dotazu dle typu slozky
		$result = dibi::query("SELECT P.id,P.link_$lang AS link,P.jmeno_$lang AS jmeno,P.popis_$lang AS popis,P.dph,(P.cena*(1+(P.dph/100))) AS scena FROM products P JOIN variants V ON P.id=V.vlastnik WHERE V.sleva > 0 AND P.status='ok' AND V.status='ok' AND show_$lang=1 GROUP BY P.id %ofs %lmt",0,5);

		foreach($result as $info)
		{
			$object = new stdClass();
			$object = clone($info);
			$result2 = dibi::query("SELECT id,cena,sklad,sleva FROM variants WHERE vlastnik=%i AND status='ok' ORDER BY cena",$info->id);
			$info2 = $result2->fetch();
			$object->image = dibi::fetch("SELECT id FROM images WHERE vlastnik=%i ORDER BY sort,id",$info->id)->id;
			$object->pocetVar = count($result2);
			$object->sklad = 0;
			$object->sleva = 0;
			if($object->pocetVar == 1)
			{
				$object->varianta = $info2->id;
				$object->sklad = $info2->sklad;
				$object->sleva = $info2->sleva;
			}else{
				foreach($result2 as $info3)
				{
					// Zjisteni jestli jsou nejake polozky skladem
					if($info3->sklad)$object->sklad = $info3->sklad;
					if(isset($info3->sleva) && $object->sleva < $info3->sleva)$object->sleva = $info3->sleva;
				}
			}

			if(isset($info) && isset($info2->cena))
			{
				if($info2->sleva)$object->cenastara = ceil($info2->cena*(1+$info->dph/100));
				$object->cena = ceil($info2->cena*(1-$info2->sleva/100)*(1+$info->dph/100));
			}
			else $object->cena = 0;
			$return[] = $object;
		}
		return $return;
	}

	public static function getWeight($user)
	{
		$result = dibi::fetch("SELECT SUM(V.hmotnost*B.count) AS hmotnost FROM basket B JOIN variants V ON V.id=B.id_var WHERE B.id_user=%i AND B.id_obj=%i",$user,0);
		return $result->hmotnost;
	}

	private function unpackZip($dir, $file, $item)
	{
		if ($zip = zip_open($dir . $file . ".zip"))
		{
			if ($zip)
			{
				while ($zip_entry = zip_read($zip))
				{
					if (zip_entry_open($zip, $zip_entry, "r"))
					{
						$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
						$dir_name = dirname(zip_entry_name($zip_entry));
						if ($dir_name != ".")
						{
							$dir_op = $dir . $file . "/";
							foreach (explode("/", $dir_name) as $k)
							{
								$dir_op = $dir_op . $k;
								if (is_file($dir_op))
									unlink($dir_op);
								if (! is_dir($dir_op))
									mkdir($dir_op);
								$dir_op = $dir_op . "/";
							}
						}
						$name = $dir . zip_entry_name($zip_entry);
						$fp = fopen($name, "w");
						fwrite($fp, $buf);
						fclose($fp);
						$this->setImage($item, $name, 'specialni');
						zip_entry_close($zip_entry);
					}
					else
						return false;
				}
				zip_close($zip);
			}
		}
		else return false;
	return true;
	}

}
