<?php

class ImageModel extends BaseModel{

	public function setImage(NImage $image,$owner,$typ)
	{

		$size = $this->context->params->image;

		$result = dibi::fetch("SELECT id FROM images WHERE vlastnik=%i AND typ=%s",$owner,$typ);
		if(isset($result->id))$id = $result->id;
		else{
			dibi::query("INSERT INTO images",array('vlastnik'=>$owner,'typ'=>$typ));
			$id = dibi::getInsertId();
		}
		if($typ == 'kategorie')dibi::query("UPDATE categories SET icon=%i WHERE id=%i",$id,$owner);

		$image->resize($size->largewidth,$size->largeheight);
		$image->save('./images/uploaded/large'.$id.'.png',NImage::PNG);
		$image->resize($size->mediumwidth,$size->mediumheight);
		$image->save('./images/uploaded/medium'.$id.'.png',NImage::PNG);
		$image->resize($size->miniwidth,$size->miniheight);
		$image->save('./images/uploaded/mini'.$id.'.png',NImage::PNG);
	}

}