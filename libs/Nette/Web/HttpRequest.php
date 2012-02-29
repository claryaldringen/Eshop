<?php

/**
 * This file is part of the Nette Framework.
 *
 * Copyright (c) 2004, 2010 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license", and/or
 * GPL license. For more information please see http://nette.org
 * @package Nette\Web
 */



/**
 * NHttpRequest provides access scheme for request sent via HTTP.
 *
 * @author     David Grudl
 *
 * @property   NUriScript $uri
 * @property-read array $query
 * @property-read array $post
 * @property-read array $files
 * @property-read array $cookies
 * @property-read string $method
 * @property-read array $headers
 * @property-read NUri $referer
 * @property-read string $remoteAddress
 * @property-read string $remoteHost
 * @property-read bool $secured
 */
class NHttpRequest extends NObject implements IHttpRequest
{
	/** @var string */
	private $method;

	/** @var NUriScript */
	private $uri;

	/** @var array */
	private $query;

	/** @var array */
	private $post;

	/** @var array */
	private $files;

	/** @var array */
	private $cookies;

	/** @var array */
	private $headers;

	/** @var string */
	private $remoteAddress;

	/** @var string */
	private $remoteHost;



	public function __construct(NUriScript $uri, $query = NULL, $post = NULL, $files = NULL, $cookies = NULL,
		$headers = NULL, $method = NULL, $remoteAddress = NULL, $remoteHost = NULL)
	{
		$this->uri = $uri;
		$this->uri->freeze();
		if ($query === NULL) {
			parse_str($uri->query, $this->query);
		} else {
			$this->query = (array) $query;
		}
		$this->post = (array) $post;
		$this->files = (array) $files;
		$this->cookies = (array) $cookies;
		$this->headers = (array) $headers;
		$this->method = $method;
		$this->remoteAddress = $remoteAddress;
		$this->remoteHost = $remoteHost;
	}



	/**
	 * Returns URL object.
	 * @return NUriScript
	 */
	final public function getUri()
	{
		return $this->uri;
	}



	/********************* query, post, files & cookies ****************d*g**/



	/**
	 * Returns variable provided to the script via URL query ($_GET).
	 * If no key is passed, returns the entire array.
	 * @param  string key
	 * @param  mixed  default value
	 * @return mixed
	 */
	final public function getQuery($key = NULL, $default = NULL)
	{
		if (func_num_args() === 0) {
			return $this->query;

		} elseif (isset($this->query[$key])) {
			return $this->query[$key];

		} else {
			return $default;
		}
	}



	/**
	 * Returns variable provided to the script via POST method ($_POST).
	 * If no key is passed, returns the entire array.
	 * @param  string key
	 * @param  mixed  default value
	 * @return mixed
	 */
	final public function getPost($key = NULL, $default = NULL)
	{
		if (func_num_args() === 0) {
			return $this->post;

		} elseif (isset($this->post[$key])) {
			return $this->post[$key];

		} else {
			return $default;
		}
	}



	/**
	 * Returns uploaded file.
	 * @param  string key (or more keys)
	 * @return NHttpUploadedFile
	 */
	final public function getFile($key)
	{
		$args = func_get_args();
		return NArrayTools::get($this->files, $args);
	}



	/**
	 * Returns uploaded files.
	 * @return array
	 */
	final public function getFiles()
	{
		return $this->files;
	}



	/**
	 * Returns variable provided to the script via HTTP cookies.
	 * @param  string key
	 * @param  mixed  default value
	 * @return mixed
	 */
	final public function getCookie($key, $default = NULL)
	{
		if (func_num_args() === 0) {
			return $this->cookies;

		} elseif (isset($this->cookies[$key])) {
			return $this->cookies[$key];

		} else {
			return $default;
		}
	}



	/**
	 * Returns variables provided to the script via HTTP cookies.
	 * @return array
	 */
	final public function getCookies()
	{
		return $this->cookies;
	}



	/********************* method & headers ****************d*g**/



	/**
	 * Returns HTTP request method (GET, POST, HEAD, PUT, ...). The method is case-sensitive.
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}



	/**
	 * Checks if the request method is the given one.
	 * @param  string
	 * @return bool
	 */
	public function isMethod($method)
	{
		return strcasecmp($this->method, $method) === 0;
	}



	/**
	 * Checks if the request method is POST.
	 * @return bool
	 */
	public function isPost()
	{
		return $this->isMethod('POST');
	}



	/**
	 * Return the value of the HTTP header. Pass the header name as the
	 * plain, HTTP-specified header name (e.g. 'Accept-Encoding').
	 * @param  string
	 * @param  mixed
	 * @return mixed
	 */
	final public function getHeader($header, $default = NULL)
	{
		$header = strtolower($header);
		if (isset($this->headers[$header])) {
			return $this->headers[$header];
		} else {
			return $default;
		}
	}



	/**
	 * Returns all HTTP headers.
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}



	/**
	 * Returns referrer.
	 * @return NUri|NULL
	 */
	final public function getReferer()
	{
		return isset($this->headers['referer']) ? new NUri($this->headers['referer']) : NULL;
	}



	/**
	 * Is the request is sent via secure channel (https).
	 * @return bool
	 */
	public function isSecured()
	{
		return $this->uri->scheme === 'https';
	}



	/**
	 * Is AJAX request?
	 * @return bool
	 */
	public function isAjax()
	{
		return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
	}



	/**
	 * Returns the IP address of the remote client.
	 * @return string
	 */
	public function getRemoteAddress()
	{
		return $this->remoteAddress;
	}



	/**
	 * Returns the host of the remote client.
	 * @return string
	 */
	public function getRemoteHost()
	{
		if (!$this->remoteHost) {
			$this->remoteHost = $this->remoteAddress ? getHostByAddr($this->remoteAddress) : NULL;
		}
		return $this->remoteHost;
	}



	/**
	 * Parse Accept-Language header and returns prefered language.
	 * @param  array   Supported languages
	 * @return string
	 */
	public function detectLanguage(array $langs)
	{
		$header = $this->getHeader('Accept-Language');
		if (!$header) {
			return NULL;
		}

		$s = strtolower($header);  // case insensitive
		$s = strtr($s, '_', '-');  // cs_CZ means cs-CZ
		rsort($langs);             // first more specific
		preg_match_all('#(' . implode('|', $langs) . ')(?:-[^\s,;=]+)?\s*(?:;\s*q=([0-9.]+))?#', $s, $matches);

		if (!$matches[0]) {
			return NULL;
		}

		$max = 0;
		$lang = NULL;
		foreach ($matches[1] as $key => $value) {
			$q = $matches[2][$key] === '' ? 1.0 : (float) $matches[2][$key];
			if ($q > $max) {
				$max = $q; $lang = $value;
			}
		}

		return $lang;
	}

}
