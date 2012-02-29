<?php

class MultiFileModel extends NObject{

	public function getFileInfo($filename)
	{
		$return = array(
			'name' => $filename, 
			'type' => filetype(TEMP_DIR.'/c-Nette.Uploaded/'.$filename), 
			'size' => filesize(TEMP_DIR.'/c-Nette.Uploaded/'.$filename), 
			'tmp_name' => TEMP_DIR.'/c-Nette.Uploaded/'.$filename, 
			'error' => 0);
		return $return;
	}
}
