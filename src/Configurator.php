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

		/** @var array */
		private static $urlMapping = array(
			'scheme' => 'remote.scheme',
			'user' => 'remote.user',
			'pass' => 'remote.password',
			'host' => 'remote.host',
			'port' => 'remote.port',
			'path' => 'remote.path',
		);


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
					$sections[$section] = $this->expandSectionConfig(
						array_change_key_case($cfg, CASE_LOWER),
						isset($this->sections[$section]) ? $this->sections[$section] : NULL
					);

				} else {
					$section = strtolower($section);

					if (isset($this->globalOptions[$section])) {
						$configuration[$section] = $cfg;
					} else {
						$globalSection[$section] = $cfg;
					}
				}
			}

			$globalSection = $this->expandSectionConfig($globalSection, $this->globalSection);

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
				if (isset($cfg['remote.host'])) {
					// generate new URL
					$cfg['remote'] = (isset($cfg['remote.scheme']) ? $cfg['remote.scheme'] : 'ftp') . "://"
						. (isset($cfg['remote.user']) ? rawurlencode($cfg['remote.user']) : '')
						. (isset($cfg['remote.password']) ? (':' . rawurlencode($cfg['remote.password'])) : '')
						. (isset($cfg['remote.user']) || isset($cfg['remote.password']) ? '@' : '')
						. $cfg['remote.host']
						. (isset($cfg['remote.port']) ? ":{$cfg['remote.port']}" : '')
						. '/' . (isset($cfg['remote.path']) ? ltrim($cfg['remote.path'], '/') : '');
				}

				foreach (self::$urlMapping as $remoteKey) {
					// vymazeme vsechny konkretni remote klice (remote.host,...)
					unset($cfg[$remoteKey]);
				}

				$config[$name] = $cfg;
			}

			return $config;
		}


		/**
		 * @return array|NULL
		 */
		private function expandSectionConfig(array $config = NULL, $originalConfig = NULL)
		{
			if ($config === NULL) {
				return NULL;
			}

			if (isset($config['remote'])) { // expans 'remote' key
				$url = parse_url($config['remote']); // rozparsujeme URL na jednotlive casti

				foreach ($url as $part => $value) { // projdeme casti
					if (isset(self::$urlMapping[$part])) { // pokud umime cast namapovat
						$remoteOption = self::$urlMapping[$part];

						if (isset($config[$remoteOption]) || isset($originalConfig[$remoteOption])) { // pokud v konfiguraci jiz existuje konkretnejsi volba (remote.host,...), tak tuto preskocime
							// $originalConfig kontrolujeme protoze 'remote' ma na dane urovni uplne nejnizsi prioritu a nechceme, aby nam prepsalo jiz existujici volby
							continue;
						}

						$config[self::$urlMapping[$part]] = $value;
					}
				}
			}

			unset($config['remote']);
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
