<?php



/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @return IRouter
	 */
	public function createRouter()
	{
		$router = new NRouteList();

		NRoute::addStyle('path', NULL);
		NRoute::setStyleProperty('path', NRoute::PATTERN, '.*?');

		NRoute::setStyleProperty('action', NRoute::FILTER_TABLE, array(
						'registrace' => 'registration',
						'kosik' => 'basket',
						'objednavka' => 'order',
						'dekujeme'=>'orderend',
						'hledani'=>'search',
						'zapomenute-heslo'=>'forgotten',
						'heslo-odeslano'=>'passsend'
		));

		$router[] = new NRoute('<action registrace|kosik|objednavka|dekujeme|objednavky|podminky|kontakt|hledani|zapomenute-heslo|heslo-odeslano>.html',array(
				'presenter' => 'Frontend',
				'action' => 'default',
		));

		$router[] = new NRoute('admin/<action>/<presenter kategorie|orders|payment|product|users|settings|texts>.html',array(
				'presenter' => 'kategorie',
				'action' => 'default',
		));

		$router[] = new NRoute('<path>/<produkt>.html',array(
				'presenter' => 'Frontend',
				'action' => 'detail',
		));

		$router[] = new NRoute('<path [0-9]+>',array(
				'presenter' => 'Frontend',
				'action' => 'default',
				'do' => 'prevod'
		));

		$router[] = new NRoute('<path>',array(
				'presenter' => 'Frontend',
				'action' => 'kategorie',
				'path' => ''
		));
		return $router;
	}

}
