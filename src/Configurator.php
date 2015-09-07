<?php
	namespace JP\FtpDeployment;


	class Configurator
	{
		/** @var array */
		private $globalOptions = array(
			'log' => TRUE,
			'colors' => TRUE,
			'tempdir' => TRUE,
		);

		/** @var array|NULL  globalni konfigurace (log, colors,...) */
		private $configuration;

		/** @var array|NULL  konfigurace sekci */
		private $sections;

		/** @var array|NULL  globalni sekce, dedi z ni jednotlive sekce */
		private $globalSection;


		/**
		 * Adds new configuration
		 * @param  string
		 */
		public function addConfig(array $config)
		{
			$configuration = NULL;
			$globalSection = NULL;
			$sections = NULL;

			foreach ($config as $section => $cfg) {
				if (is_array($cfg)) {
					$sections[$section] = array_change_key_case($cfg, CASE_LOWER);

				} else {
					$section = strtolower($section);

					if (isset($this->globalOptions[$section])) {
						$configuration[$section] = $cfg;
					} else {
						$globalSection[$section] = $cfg;
					}
				}
			}

			$this->config = self::merge($configuration, $this->config);
			$this->configuration = self::merge($configuration, $this->configuration);
			$this->sections = self::merge($sections, $this->sections);
			$this->globalSection = self::merge($globalSection, $this->globalSection);
		}


		/**
		 * Adds new config file
		 * @param  string
		 */
		public function addFile($file, $primaryFile = FALSE)
		{
			if (pathinfo($file, PATHINFO_EXTENSION) == 'php') {
				$config = include $file;
			} else {
				$config = parse_ini_file($file, TRUE);
			}

			if ($config === FALSE) {
				throw new Exception("Problem with file $file (not found or syntax error)");
			}

			if ($primaryFile && isset($config['includes'])) {
				$basePath = pathinfo($file, PATHINFO_DIRNAME);

				foreach ((array) $config['includes'] as $_file) {
					$this->addFile("$basePath/$_file", FALSE);
				}
			}

			unset($config['includes']);
			$this->addConfig($config);
		}


		/**
		 * Returns configuration
		 * @return array  [section => config]
		 */
		public function getConfig()
		{
			static $remoteKeys = array(
				'remote.user' => 'user',
				'remote.password' => 'pass',
				'remote.host' => 'host',
				'remote.port' => 'port',
				'remote.path' => 'path',
				'remote.scheme' => 'scheme',
			);

			$sections = $this->sections;

			if (empty($sections)) {
				$sections[''] = NULL;
			}

			$config = $this->configuration; // vychozi globalni konfigurace (colors,...)

			foreach ($sections as $name => $cfg) {
				$cfg = self::merge($cfg, $this->globalSection); // priradime ke konfiguraci globalni sekci

				unset(
					$cfg['log'],
					$cfg['tempdir'],
					$cfg['colors']
				);

				// build 'remote' key
				$remoteParts = NULL;

				if (isset($cfg['remote'])) {
					$remoteParts = parse_url($cfg['remote']);
				}

				foreach ($remoteKeys as $remoteKey => $urlKey) {
					if (isset($cfg[$remoteKey])) {
						$remoteParts[$urlKey] = $cfg[$remoteKey];
						unset($cfg[$remoteKey]);
					}
				}

				if (isset($remoteParts['host'])) {
					// generate new URL
					$cfg['remote'] = (isset($remoteParts['scheme']) ? $remoteParts['scheme'] : 'ftp') . "://"
						. (isset($remoteParts['user']) ? rawurlencode($remoteParts['user']) : '')
						. (isset($remoteParts['pass']) ? (':' . rawurlencode($remoteParts['pass'])) : '')
						. (isset($remoteParts['user']) || isset($remoteParts['pass']) ? '@' : '')
						. $remoteParts['host']
						. (isset($remoteParts['port']) ? ":{$remoteParts['port']}" : '')
						. '/' . (isset($remoteParts['path']) ? ltrim($remoteParts['path'], '/') : '');
				}

				$config[$name] = $cfg;
			}

			return $config;
		}


		/**
		 * Merges configurations. Left has higher priority than right one.
		 */
		public static function merge($left, $right)
		{
			if (is_array($left) && is_array($right)) {
				foreach ($left as $key => $val) {
					if (is_int($key)) {
						$right[] = $val;
					} else {
						if (isset($right[$key])) {
							$val = self::merge($val, $right[$key]);
						}
						$right[$key] = $val;
					}
				}
				return $right;

			} elseif ($left === NULL && is_array($right)) {
					return $right;

			} else {
					return $left;
			}
		}
	}
