<?php

// pokud používáte verzi pro PHP 5.3, odkomentujte následující řádek:
// use Nette\Debug, Nette\NEnvironment, Nette\Application\SimpleRouter;



// Step 1: Load Nette Framework
require_once LIB_DIR . '/Nette/loader.php';
require_once LIB_DIR . '/dibi/dibi.php';

// Step 2: Enable Nette\Debug for better exception and error visualisation
NDebugger::$strictMode = TRUE;
NDebugger::enable(NDebugger::DETECT,APP_DIR.'/../log','webmaster@mercatores.cz');

// Load configuration from config.neon file
$configurator = new NConfigurator;
$configurator->loadConfig(dirname(__FILE__) . '/config.ini');

// Configure application
$application = $configurator->container->application;
$application->errorPresenter = 'Error';

$session = NEnvironment::getSession();
$session->setExpiration(15552000);//180 dní
if (!$session->isStarted())$session->start();

// Step 5: Setup application router
$router = $application->getRouter();

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

$router[] = new NRoute('admin/<action>/<presenter kategorie|orders|payment|product|users|settings>.html',array(
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



//$application->setRouter(new NSimpleRouter('Kategorie:default'));

// Step 6: Run the application!
if (!NEnvironment::isConsole())
{
	$application->run();
}

