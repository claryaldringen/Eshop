<?php

class KategorieModel extends BaseModel{


	public function getTreeDataSource($lang)
	{
		return dibi::dataSource("SELECT id,vlastnik AS parentId,jmeno_$lang AS name,link_$lang AS slug FROM categories WHERE status='ok' ORDER BY sort");
	}

	public function setData($id,NFormNControl $data)
	{
		$update[$data->getName()] = $data->getValue();
		if($update[$data->getName()] == '')$update[$data->getName()] = '-';
		foreach($this->getLanguages() as $info)
		{
			if(isset($update['jmeno_'.$info->zkratka]))$update['link_'.$info->zkratka] = NStrings::webalize($update['jmeno_'.$info->zkratka]);
		}
		return dibi::query("UPDATE categories SET ",$update," WHERE id=%i",$id);
	}

	public function deleteItem($id)
	{
		return dibi::query("DELETE FROM categories WHERE id=%i",$id);
	}

	public function getLanguages()
	{
		$result = dibi::query("SELECT zkratka FROM languages");
		return $result->fetchAll();
	}

	public function getOwners()
	{
		$result = dibi::fetch("SELECT zkratka FROM languages");
		$result = dibi::query("SELECT id,jmeno_".$result->zkratka." AS jmeno FROM categories ORDER BY id DESC");
		return $result->fetchPairs('id','jmeno');
	}

	public function getObsah($id,$lang)
	{
		$result = dibi::fetch("SELECT text_".$lang." AS obsah FROM categories WHERE id=%i",$id);
		return $result->obsah;
	}

	public function getCategories($owner,$lang,$type='normal',$status='ok')
	{
		$res = array();
		$result = dibi::query("SELECT id,icon AS image,jmeno_$lang AS jmeno,vlastnik FROM categories WHERE vlastnik=%i AND type=%s AND status=%s ORDER BY sort",$owner,$type,$status)->fetchAll();
		foreach($result as $info)
		{
			$info->path = $this->getPathFromId($info->id,$lang);
			$info->image = dibi::query("SELECT id FROM images WHERE vlastnik=%i AND typ='kategorie'", $info->id)->fetchSingle();
			$res[] = $info;
		}
		return $res;
	}

	public function getCategoriesRecursive($owner,$lang)
	{
		$ids = '';
		$cats = $this->getCategories($owner,$lang);
		foreach($cats as $cat)
		{
			if($ids)$ids .= ','.$cat->id;
			else $ids = $cat->id;
			$id = $this->getCategoriesRecursive($cat->id,$lang);
			if($id)$ids .= ','.$id;
		}
		return $ids;
	}

	public function getAllCats($lang)
	{
		return dibi::query("SELECT id,jmeno_$lang AS jmeno FROM categories WHERE status='ok' AND type='normal'")->fetchPairs('id','jmeno');
	}

	public function getPathFromId($id,$lang)
	{
		$path = '';
		while($id > 0)
		{
			$result = dibi::fetch("SELECT vlastnik,link_$lang AS link FROM categories WHERE status='ok' AND id=%i",$id);
			if(isset($result->vlastnik)) {
				$id = $result->vlastnik;
				$path = $result->link . '/' . $path;
			} else break;
		}
		return $path;
	}

	public function getIdFromPath($path,$lang)
	{
		$pole = explode('/',$path);
		$owner = new stdClass();
		$owner->id = 0;
		foreach($pole as $folder)
		{
			if($folder != '')
			{
				if(!isset($owner->id))break;
				$owner = dibi::fetch("SELECT id FROM categories WHERE link_$lang=%s AND vlastnik=%i AND status='ok'", $folder, $owner->id);
			}
		}
		if(isset($owner->id))return $owner->id;
		else throw new NBadRequestException();
	}

	public function getCategory($id,$lang,$hort=true)
	{
		$result = dibi::fetch("SELECT id,icon AS image,jmeno_$lang AS jmeno,text_$lang AS text,vlastnik,type FROM categories WHERE id=%i",$id);
		if($hort && isset($result->text))
		{
			$result->text = NStrings::truncate($result->text, '300');
			$result->length = strlen($result->text);
		}
		return $result;
	}

	public function move($co,$kam)
	{
		dibi::query("UPDATE categories SET vlastnik=%i WHERE id IN ('".implode("','",$co)."')",$kam);
	}

	public function setName($name,$id,$lang)
	{
		dibi::query("UPDATE categories SET jmeno_".$lang."=%s,link_".$lang."=%s WHERE id=%i",$name, NStrings::webalize($name), $id);
	}

	public function newCat($owner,$type)
	{
		$langs = $this->getLanguages();
		foreach($langs as $lang)
		{
			$pole['jmeno_'.$lang->zkratka] = 'Nová složka';
			$pole['link_'.$lang->zkratka] = md5(microtime());
		}
		if($type == 'collection')$pole['vlastnik'] = 0;
		else $pole['vlastnik'] = $owner;
		$pole['type'] = $type;
		dibi::query("INSERT INTO categories",$pole);
	}

	public function delete($co,$status)
	{
		$result = dibi::query("SELECT id FROM categories WHERE vlastnik IN %in",$co)->fetchPairs('id','id');
		if(!empty($result))$this->delete($co,$status);
		dibi::query("UPDATE products SET status=%s WHERE owner IN %in",$status,$co);
		dibi::query("UPDATE categories SET status=%s WHERE id IN %in",$status,$co);
	}

	public function getText($id,$lang)
	{
		$res = dibi::fetch("SELECT text_$lang AS text FROM categories WHERE id=%i",$id);
		if(isset($res->text))return $res->text;
	}

	public function setText($id,$text,$lang)
	{
		dibi::query("UPDATE categories SET text_$lang=%s WHERE id=%i",$text,$id);
	}

	public function setImage($id, NHttpUploadedFile $file)
	{
		if($file->isOk())$this->getInstanceOf('ImageModel')->setImage($file->toImage(), $id, 'kategorie');
		return $this;
	}
}
