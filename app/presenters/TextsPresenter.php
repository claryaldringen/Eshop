<?php

/**
 * Description of TextsPresenter
 *
 * @author clary
 */
class TextsPresenter extends BasePresenter
{
	/** @persistent */
	public $langId = 1;

	/** @persistent */
	public $pageId;

	public function renderDefault()
	{
		$this->template->langs = $this->getInstanceOf('SettingsModel')->getLangs();
		$this->template->pages = $this->getInstanceOf('SettingsModel')->getPages();
	}


	public function createComponentTextForm()
	{
		$text = $this->getInstanceOf('SettingsModel')->getPageText($this->pageId, $this->langId);
		$value = $text ? $text->content : NULL;

		$form = new NAppForm($this, 'textForm');
		$form->addTextArea('text')->setDefaultValue($value);
		$form->onSuccess[] = array($this, 'textFormSubmited');
		return $form;
	}


	public function textFormSubmited(NAppForm $form)
	{
		$text = $this->getInstanceOf('SettingsModel')->getPageText($this->pageId, $this->langId);
		$id = !empty($text) ? $text->id : NULL;
		$this->getInstanceOf('SettingsModel')->setPageText($id, $form['text']->getValue(), $this->pageId, $this->langId);
		$this->redirect('this');
	}
}
