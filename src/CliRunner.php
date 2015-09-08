<?php
	namespace JP\FtpDeployment;
	use Deployment;


	class CliRunner extends Deployment\CliRunner
	{
		protected function loadConfigFile($file)
		{
			$configurator = new Configurator;

			try {
				$configurator->addFile($file, TRUE);

			} catch (ConfiguratorException $e) {
				echo 'Error: ', $e->getMessage();
				return FALSE;
			}

			return $configurator->getConfig();
		}
	}
