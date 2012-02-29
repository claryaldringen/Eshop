<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette\Application\Diagnostics
 */







/**
 * Routing debugger for Debug Bar.
 *
 * @author     David Grudl
 */
class NRoutingDebugger extends NObject implements IBarPanel
{
	/** @var IRouter */
	private $router;

	/** @var IHttpRequest */
	private $httpRequest;

	/** @var array */
	private $routers = array();

	/** @var NPresenterRequest */
	private $request;



	public static function initialize(NApplication $application, IHttpRequest $httpRequest)
	{
		NDebugger::$bar->addPanel(new self($application->getRouter(), $httpRequest));
		NDebugger::$blueScreen->addPanel(create_function('$e', 'extract(NClosureFix::$vars['.NClosureFix::uses(array('application'=>$application)).'], EXTR_REFS);
			if ($e === NULL) {
				return array(
					\'tab\' => \'Nette Application\',
					\'panel\' => \'<h3>Requests</h3>\' . NDebugHelpers::clickableDump($application->getRequests())
						. \'<h3>Presenter</h3>\' . NDebugHelpers::clickableDump($application->getPresenter())
				);
			}
		'));
	}



	public function __construct(IRouter $router, IHttpRequest $httpRequest)
	{
		$this->router = $router;
		$this->httpRequest = $httpRequest;
	}



	/**
	 * Renders tab.
	 * @return string
	 */
	public function getTab()
	{
		$this->analyse($this->router);
		ob_start();
		require dirname(__FILE__) . '/templates/RoutingPanel.tab.phtml';
		return ob_get_clean();
	}



	/**
	 * Renders panel.
	 * @return string
	 */
	public function getPanel()
	{
		ob_start();
		require dirname(__FILE__) . '/templates/RoutingPanel.panel.phtml';
		return ob_get_clean();
	}



	/**
	 * Analyses simple route.
	 * @param  IRouter
	 * @return void
	 */
	private function analyse($router)
	{
		if ($router instanceof NRouteList) {
			foreach ($router as $subRouter) {
				$this->analyse($subRouter);
			}
			return;
		}

		$request = $router->match($this->httpRequest);
		$matched = $request === NULL ? 'no' : 'may';
		if ($request !== NULL && empty($this->request)) {
			$this->request = $request;
			$matched = 'yes';
		}

		$this->routers[] = array(
			'matched' => $matched,
			'class' => get_class($router),
			'defaults' => $router instanceof NRoute || $router instanceof NSimpleRouter ? $router->getDefaults() : array(),
			'mask' => $router instanceof NRoute ? $router->getMask() : NULL,
			'request' => $request,
		);
	}

}
