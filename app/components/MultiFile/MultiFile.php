<?php

class MultiFile extends NControl{

	protected $name;
	protected $label;
	protected $allowedExtensions;
	protected $maxSize;
	protected $minSize;

	public $onOneSubmit;
	public $onAllSubmit;

	public function __construct(NPresenter $presenter,$name,$label,$allowedExtensions = array(),$minSize=1,$maxSize=10485760)
	{
		parent::__construct($presenter, $name);
		$this->name = $name;
		$this->label = $label;
		$this->allowedExtensions = $allowedExtensions;
		$this->minSize = $minSize;
		$this->maxSize = $maxSize;
		if(!file_exists($this->presenter->context->params['tempDir'] . '/c-Nette.Uploaded/'))mkdir($this->presenter->context->params['tempDir'] . '/c-Nette.Uploaded/');
		else{
			$slozka = dir($this->presenter->context->params['tempDir'] . '/c-Nette.Uploaded/');
			while($soubor = $slozka->read())
			{
				if ($soubor=="." || $soubor==".." || is_dir($this->presenter->context->params['tempDir'] . '/c-Nette.Uploaded/'.$soubor)) continue;
				if(filemtime($this->presenter->context->params['tempDir'] . '/c-Nette.Uploaded/'.$soubor) < (time()-3600*24))unlink($this->presenter->context->params['tempDir'] . '/c-Nette.Uploaded/'.$soubor);
			}
			$slozka->close();
		}
		if(!$presenter->isAjax())
		{
			$session = NEnvironment::getSession('multiFile');
			$session->count = 0;
			$session->files = array();
		}
	}

	public function render()
	{
		$template = $this->createTemplate();
		$template->setFile(dirname(__FILE__).'/multifile.phtml');
		$template->name = $this->name;
		$template->label = $this->label;
		$template->link = $this->link('getFile!');
		if(!empty($this->allowedExtensions))$template->allowedExtensions = "'".implode("','",$this->allowedExtensions)."'";
		else $template->allowedExtensions = '';
		$template->minSize = $this->minSize;
		$template->maxSize = $this->maxSize;
		$template->render();
	}

	public function handleGetFile()
	{
		$session = NEnvironment::getSession('multiFile');
		$session->count++;
		$uploader = new qqFileUploader($this->allowedExtensions, $this->maxSize);
		if(!file_exists($this->presenter->context->params['tempDir'] . '/c-Nette.Uploaded/'))mkdir($this->presenter->context->params['tempDir'] . '/c-Nette.Uploaded/');
		$result = $uploader->handleUpload($this->presenter->context->params['tempDir'] . '/c-Nette.Uploaded/');
		$model = new MultiFileModel($this->presenter->context->params['tempDir']);
		$session->files[] = new NHttpUploadedFile($model->getFileInfo($_GET['qqfile']));
		$this->onOneSubmit(new NHttpUploadedFile($model->getFileInfo($_GET['qqfile'])));
		if($session->count >= $_GET['total'])
		{
			$files = $session->files;
			$session->files = array();
			$session->count = 0;
			$this->onAllSubmit($files);

		}else{
			echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
			die;
		}
	}
}
