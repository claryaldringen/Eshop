<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette
 */







/**
 * Initial system DI container generator.
 *
 * @author     David Grudl
 */
class NConfigurator extends NObject
{
	public static $instance;

	/** @var string */
	public $defaultConfigFile = '%appDir%/config.neon';

	/** @var NDiContainer */
	private $container;


	public function __construct($containerClass = 'NDiContainer')
	{
		self::$instance = $this;
		$this->container = new $containerClass;

		foreach (get_class_methods($this) as $name) {
			if (substr($name, 0, 13) === 'createService' ) {
				$this->container->addService(strtolower($name[13]) . substr($name, 14), array(__CLASS__, $name));
			}
		}

		$this->container->params = new NArrayHash;
		defined('WWW_DIR') && $this->container->params['wwwDir'] = realpath(WWW_DIR);
		defined('APP_DIR') && $this->container->params['appDir'] = realpath(APP_DIR);
		defined('LIBS_DIR') && $this->container->params['libsDir'] = realpath(LIBS_DIR);
		defined('TEMP_DIR') && $this->container->params['tempDir'] = realpath(TEMP_DIR);
		$this->container->params['productionMode'] = self::detectProductionMode();
		$this->container->params['consoleMode'] = PHP_SAPI === 'cli';
	}



	/**
	 * Get initial instance of DI container.
	 * @return NDiContainer
	 */
	public function getContainer()
	{
		return $this->container;
	}



	/**
	 * Loads configuration from file and process it.
	 * @return void
	 */
	public function loadConfig($file, $section = NULL)
	{
		if ($file === NULL) {
			$file = $this->defaultConfigFile;
		}
		$container = $this->container;
		$file = $container->expand($file);
		if (!is_file($file)) {
			$file = preg_replace('#\.neon$#', '.ini', $file); // back compatibility
		}
		if ($section === NULL) {
			if (PHP_SAPI === 'cli') {
				$section = NEnvironment::CONSOLE;
			} else {
				$section = $container->params['productionMode'] ? NEnvironment::PRODUCTION : NEnvironment::DEVELOPMENT;
			}
		}

		$cache = new NCache($container->templateCacheStorage, 'Nette.Configurator');
		$cacheKey = array((array) $container->params, $file, $section);
		$cached = $cache->load($cacheKey);
		if ($cached) {
			require $cached['file'];
			fclose($cached['handle']);
			return;
		}

		$config = NConfig::fromFile($file, $section);
		$code = "<?php\n// source file $file\n\n";

		// back compatibility with singular names
		foreach (array('service', 'variable') as $item) {
			if (isset($config[$item])) {
				trigger_error(basename($file) . ": Section '$item' is deprecated; use plural form '{$item}s' instead.", E_USER_WARNING);
				$config[$item . 's'] = $config[$item];
				unset($config[$item]);
			}
		}

		// add expanded variables
		while (!empty($config['variables'])) {
			$old = $config['variables'];
			foreach ($config['variables'] as $key => $value) {
				try {
					$code .= $this->generateCode('$container->params[?] = ?', $key, $container->params[$key] = $container->expand($value));
					unset($config['variables'][$key]);
				} catch (InvalidArgumentException $e) {}
			}
			if ($old === $config['variables']) {
				throw new InvalidStateException("Unable to expand variables: " . implode(', ', array_keys($old)) . ".");
			}
		}
		unset($config['variables']);

		// process services
		if (isset($config['services'])) {
			foreach ($config['services'] as $key => & $def) {
				if (preg_match('#^Nette\\\\.*\\\\I?([a-zA-Z]+)$#', strtr($key, '-', '\\'), $m)) { // back compatibility
					$m[1][0] = strtolower($m[1][0]);
					trigger_error(basename($file) . ": service name '$key' has been renamed to '$m[1]'", E_USER_WARNING);
					$key = $m[1];
				}

				if (is_scalar($def)) {
					$def = array('class' => $def);
				}

				if (method_exists(__CLASS__, "createService$key")) {
					$container->removeService($key);
					if (!isset($def['factory']) && !isset($def['class'])) {
						$def['factory'] = array(__CLASS__, "createService$key");
					}
				}

				if (isset($def['option'])) {
					$def['arguments'][] = $def['option'];
				}

				if (!empty($def['run'])) {
					$def['tags'] = array('run');
				}
			}
			$builder = new NContainerBuilder;
			$code .= $this->generateCode('$builder = new '.get_class($builder).'; $builder->addDefinitions($container, ?)', $config['services']);
			unset($config['services']);
		}

		// expand variables
		array_walk_recursive($config, create_function('&$val', '
			$val = NEnvironment::expand($val);
		'));

		// PHP settings
		if (isset($config['php'])) {
			foreach ($config['php'] as $key => $value) {
				if (is_array($value)) { // back compatibility - flatten INI dots
					foreach ($value as $k => $v) {
						$code .= $this->configurePhp("$key.$k", $v);
					}
				} else {
					$code .= $this->configurePhp($key, $value);
				}
			}
			unset($config['php']);
		}

		// define constants
		if (isset($config['const'])) {
			foreach ($config['const'] as $key => $value) {
				$code .= $this->generateCode('define', $key, $value);
			}
			unset($config['const']);
		}

		// set modes - back compatibility
		if (isset($config['mode'])) {
			trigger_error(basename($file) . ": Section 'mode' is deprecated; use 'params' instead.", E_USER_WARNING);
			foreach ($config['mode'] as $mode => $state) {
				$code .= $this->generateCode('$container->params[?] = ?', $mode . 'Mode', (bool) $state);
			}
			unset($config['mode']);
		}

		// other
		foreach ($config as $key => $value) {
			$code .= $this->generateCode('$container->params[?] = ' . (is_array($value) ? 'NArrayHash::from(?)' : '?'), $key, $value);
		}

		// pre-loading
		$code .= self::preloadEnvironment($container);

		// auto-start services
		$code .= 'foreach ($container->getServiceNamesByTag("run") as $name => $foo) { $container->getService($name); }' . "\n";

		$cache->save($cacheKey, $code, array(
			NCache::FILES => $file,
		));

		NLimitedScope::evaluate($code, array('container' => $container));
	}



	/********************* tools ****************d*g**/



	/**
	 * Detects production mode by IP address.
	 * @return bool
	 */
	public static function detectProductionMode()
	{
		if (!isset($_SERVER['SERVER_ADDR']) && !isset($_SERVER['LOCAL_ADDR'])) {
			return TRUE;
		}
		$addrs = array();
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { // proxy server detected
			$addrs = preg_split('#,\s*#', $_SERVER['HTTP_X_FORWARDED_FOR']);
		}
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$addrs[] = $_SERVER['REMOTE_ADDR'];
		}
		$addrs[] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
		foreach ($addrs as $addr) {
			$oct = explode('.', $addr);
			// 10.0.0.0/8   Private network
			// 127.0.0.0/8  Loopback
			// 169.254.0.0/16 & ::1  Link-Local
			// 172.16.0.0/12  Private network
			// 192.168.0.0/16  Private network
			if ($addr !== '::1' && (count($oct) !== 4 || ($oct[0] !== '10' && $oct[0] !== '127' && ($oct[0] !== '172' || $oct[1] < 16 || $oct[1] > 31)
				&& ($oct[0] !== '169' || $oct[1] !== '254') && ($oct[0] !== '192' || $oct[1] !== '168')))
			) {
				return TRUE;
			}
		}
		return FALSE;
	}



	public function configurePhp($name, $value)
	{
		if (!is_scalar($value)) {
			throw new InvalidStateException("Configuration value for directive '$name' is not scalar.");
		}

		switch ($name) {
		case 'include_path':
			return $this->generateCode('set_include_path', str_replace(';', PATH_SEPARATOR, $value));
		case 'ignore_user_abort':
			return $this->generateCode('ignore_user_abort', $value);
		case 'max_execution_time':
			return $this->generateCode('set_time_limit', $value);
		case 'date.timezone':
			return $this->generateCode('date_default_timezone_set', $value);
		}

		if (function_exists('ini_set')) {
			return $this->generateCode('ini_set', $name, $value);
		} elseif (ini_get($name) != $value) { // intentionally ==
			throw new NotSupportedException('Required function ini_set() is disabled.');
		}
	}



	private static function generateCode($statement)
	{
		$args = func_get_args();
		unset($args[0]);
		foreach ($args as &$arg) {
			$arg = var_export($arg, TRUE);
		}
		if (strpos($statement, '?') === FALSE) {
			return $statement .= '(' . implode(', ', $args) . ");\n\n";
		}
		$a = strpos($statement, '?');
		$i = 1;
		while ($a !== FALSE) {
			$statement = substr_replace($statement, $args[$i], $a, 1);
			$a = strpos($statement, '?', $a + strlen($args[$i]));
			$i++;
		}
		return $statement . ";\n\n";
	}



	/********************* service factories ****************d*g**/



	/**
	 * @return NApplication
	 */
	public static function createServiceApplication(NDiContainer $container, array $options = NULL)
	{
		$context = new NDiContainer;
		$context->addService('httpRequest', $container->httpRequest);
		$context->addService('httpResponse', $container->httpResponse);
		$context->addService('session', $container->session);
		$context->addService('presenterFactory', $container->presenterFactory);
		$context->addService('router', $container->router);

		NPresenter::$invalidLinkMode = $container->params['productionMode']
			? NPresenter::INVALID_LINK_SILENT
			: NPresenter::INVALID_LINK_WARNING;

		$class = isset($options['class']) ? $options['class'] : 'NApplication';
		$application = new $class($context);
		$application->catchExceptions = $container->params['productionMode'];
		if ($container->session->exists()) {
		$application->onStartup[] = create_function('', 'extract(NClosureFix::$vars['.NClosureFix::uses(array('container'=>$container)).'], EXTR_REFS);
				$container->session->start(); // opens already started session
			');
			}
		return $application;
	}



	/**
	 * @return IPresenterFactory
	 */
	public static function createServicePresenterFactory(NDiContainer $container)
	{
		return new NPresenterFactory(
			isset($container->params['appDir']) ? $container->params['appDir'] : NULL,
			$container
		);
	}



	/**
	 * @return IRouter
	 */
	public static function createServiceRouter(NDiContainer $container)
	{
		return new NRouteList;
	}



	/**
	 * @return NHttpRequest
	 */
	public static function createServiceHttpRequest()
	{
		$factory = new NHttpRequestFactory;
		$factory->setEncoding('UTF-8');
		return $factory->createHttpRequest();
	}



	/**
	 * @return NHttpResponse
	 */
	public static function createServiceHttpResponse()
	{
		$response = new NHttpResponse;
		if (!$response->isSent()) {
			$response->setContentType('text/html', 'utf-8');
		}
		return $response;
	}



	/**
	 * @return NHttpContext
	 */
	public static function createServiceHttpContext(NDiContainer $container)
	{
		return new NHttpContext($container->httpRequest, $container->httpResponse);
	}



	/**
	 * @return NSession
	 */
	public static function createServiceSession(NDiContainer $container, array $options = NULL)
	{
		$session = new NSession($container->httpRequest, $container->httpResponse);
		$session->setOptions((array) $options);
		if (isset($options['expiration'])) {
			$session->setExpiration($options['expiration']);
		}
		return $session;
	}



	/**
	 * @return NUser
	 */
	public static function createServiceUser(NDiContainer $container)
	{
		$context = new NDiContainer;
		// copies services from $container and preserves lazy loading
		$context->addService('authenticator', create_function('', 'extract(NClosureFix::$vars['.NClosureFix::uses(array('container'=>$container)).'], EXTR_REFS);
			return $container->authenticator;
		'));
		$context->addService('authorizator', create_function('', 'extract(NClosureFix::$vars['.NClosureFix::uses(array('container'=>$container)).'], EXTR_REFS);
			return $container->authorizator;
		'));
		$context->addService('session', $container->session);
		return new NUser($context);
	}



	/**
	 * @return ICacheStorage
	 */
	public static function createServiceCacheStorage(NDiContainer $container)
	{
		if (!isset($container->params['tempDir'])) {
			throw new InvalidStateException("Service cacheStorage requires that parameter 'tempDir' contains path to temporary directory.");
		}
		$dir = $container->expand('%tempDir%/cache');
		umask(0000);
		@mkdir($dir, 0777); // @ - directory may exists
		return new NFileStorage($dir, $container->cacheJournal);
	}



	/**
	 * @return ICacheStorage
	 */
	public static function createServiceTemplateCacheStorage(NDiContainer $container)
	{
		if (!isset($container->params['tempDir'])) {
			throw new InvalidStateException("Service templateCacheStorage requires that parameter 'tempDir' contains path to temporary directory.");
		}
		$dir = $container->expand('%tempDir%/cache');
		umask(0000);
		@mkdir($dir, 0777); // @ - directory may exists
		return new NPhpFileStorage($dir);
	}



	/**
	 * @return ICacheJournal
	 */
	public static function createServiceCacheJournal(NDiContainer $container)
	{
		return new NFileJournal($container->params['tempDir']);
	}



	/**
	 * @return IMailer
	 */
	public static function createServiceMailer(NDiContainer $container, array $options = NULL)
	{
		if (empty($options['smtp'])) {
			return new NSendmailMailer;
		} else {
			return new NSmtpMailer($options);
		}
	}



	/**
	 * @return NRobotLoader
	 */
	public static function createServiceRobotLoader(NDiContainer $container, array $options = NULL)
	{
		$loader = new NRobotLoader;
		$loader->autoRebuild = isset($options['autoRebuild']) ? $options['autoRebuild'] : !$container->params['productionMode'];
		$loader->setCacheStorage($container->cacheStorage);
		if (isset($options['directory'])) {
			$loader->addDirectory($options['directory']);
		} else {
			foreach (array('appDir', 'libsDir') as $var) {
				if (isset($container->params[$var])) {
					$loader->addDirectory($container->params[$var]);
				}
			}
		}
		$loader->register();
		return $loader;
	}



	public static function preloadEnvironment(NDiContainer $container)
	{
		$code = '';
		$dir = $container->expand('%tempDir%/cache');
		umask(0000);
		@mkdir($dir, 0777); // @ - directory may exists

		// checks whether directory is writable
		$uniq = uniqid('_', TRUE);
		umask(0000);
		if (!@mkdir("$dir/$uniq", 0777)) { // @ - is escalated to exception
			throw new InvalidStateException("Unable to write to directory '$dir'. Make this directory writable.");
		}

		// tests subdirectory mode
		$useDirs = @file_put_contents("$dir/$uniq/_", '') !== FALSE; // @ - error is expected
		@unlink("$dir/$uniq/_");
		@rmdir("$dir/$uniq"); // @ - directory may not already exist

		$code .= self::generateCode('NFileStorage::$useDirectories = ?', $useDirs);
		return $code;
	}

}
