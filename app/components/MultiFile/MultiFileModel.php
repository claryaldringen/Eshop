<?php

class MultiFileModel extends NObject{

	private $tempDir;

	public function __construct($tempDir) {
		$this->tempDir = $tempDir;
	}


	public function getFileInfo($filename)
	{
		$return = array(
			'name' => $filename,
			'type' => filetype($this->tempDir . '/c-Nette.Uploaded/'.$filename),
			'size' => filesize($this->tempDir . '/c-Nette.Uploaded/'.$filename),
			'tmp_name' => $this->tempDir . '/c-Nette.Uploaded/'.$filename,
			'error' => 0);
		return $return;
	}
}
