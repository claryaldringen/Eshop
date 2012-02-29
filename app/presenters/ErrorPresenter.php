<?php

/** 
 * @author Clary
 * 
 * 
 */
class ErrorPresenter extends NPresenter{
	
  public function renderDefault($exception) 
  {
			if ($exception instanceof NBadRequestException) {
				
				$this->flashMessage('Je nám líto, ale požadovaná stránka nebyla na našem serveru nalezena.');
				$this->redirect('Frontend:default');
			} 
			else 
			{
				$this->template->title = '500 Vnitřní chyba serveru';
				$this->changeAction ( '500' );
				
				NDebugger::log($exception);
			}
	}
}
