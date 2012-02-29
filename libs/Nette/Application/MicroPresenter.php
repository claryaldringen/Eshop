<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package NetteModule
 */







/**
 * Micro presenter.
 *
 * @author     David Grudl
 */
class MicroPresenter extends NObject implements IPresenter
{
	/** @var IDiContainer */
	private $context;

	/** @var NPresenterRequest */
	private $request;



	/**
	 * @param  NPresenterRequest
	 * @return IPresenterResponse
	 */
	public function run(NPresenterRequest $request)
	{
		$this->request = $request;

		$httpRequest = $this->context->httpRequest;
		if (!$httpRequest->isAjax() && ($request->isMethod('get') || $request->isMethod('head'))) {
			$refUrl = clone $httpRequest->getUrl();
			$url = $this->context->router->constructUrl($request, $refUrl->setPath($refUrl->getScriptPath()));
			if ($url !== NULL && !$httpRequest->getUrl()->isEqual($url)) {
				return new NRedirectResponse($url, IHttpResponse::S301_MOVED_PERMANENTLY);
			}
		}

		$params = $request->getParams();
		if (!isset($params['callback'])) {
			return;
		}
		$params['presenter'] = $this;
		$response = callback($params['callback'])->invokeNamedArgs($params);

		if (is_string($response)) {
			$response = array($response, array());
		}
		if (is_array($response)) {
			if ($response instanceof SplFileInfo) {
				$response = $this->createTemplate('NFileTemplate')
					->setParams($response[1])->setFile($response[0]);
			} else {
				$response = $this->createTemplate('NTemplate')
					->setParams($response[1])->setSource($response[0]);
			}
		}
		if ($response instanceof ITemplate) {
			return new NTextResponse($response);
		} else {
			return $response;
		}
	}



	/**
	 * Template factory
	 * @param  string
	 * @param  callback
	 * @return ITemplate
	 */
	public function createTemplate($class = NULL, $latteFactory = NULL)
	{
		$template = $class ? new $class : new NFileTemplate;

		$template->setParams($this->request->getParams());
		$template->presenter = $this;
		$template->context = $context = $this->context;
		$url = $context->httpRequest->getUrl();
		$template->baseUrl = rtrim($url->getBaseUrl(), '/');
		$template->basePath = rtrim($url->getBasePath(), '/');

		$template->registerHelperLoader('NTemplateHelpers::loader');
		$template->setCacheStorage($context->templateCacheStorage);
		$template->onPrepareFilters[] = create_function('$template', 'extract(NClosureFix::$vars['.NClosureFix::uses(array('latteFactory'=>$latteFactory,'context'=> $context)).'], EXTR_REFS);
			$template->registerFilter($latteFactory ? $latteFactory() : new NLatteFilter);
		');
		return $template;
	}



	/**
	 * Redirects to another URL.
	 * @param  string
	 * @param  int HTTP code
	 * @return void
	 */
	public function redirectUrl($url, $code = IHttpResponse::S302_FOUND)
	{
		return new NRedirectResponse($url, $code);
	}



	/**
	 * Throws HTTP error.
	 * @param  int HTTP error code
	 * @param  string
	 * @return void
	 * @throws NBadRequestException
	 */
	public function error($code, $message = NULL)
	{
		throw new NBadRequestException($message, $code);
	}



	/**
	 * @return IRequest
	 */
	public function getRequest()
	{
		return $this->request;
	}



	/********************* services ****************d*g**/



	/**
	 * Gets the context.
	 * @return Presenter  provides a fluent interface
	 */
	public function setContext(IDiContainer $context)
	{
		$this->context = $context;
		return $this;
	}



	/**
	 * Gets the context.
	 * @return IDiContainer
	 */
	final public function getContext()
	{
		return $this->context;
	}

}
