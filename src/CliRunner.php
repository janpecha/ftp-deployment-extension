<?php
	namespace JP\FtpDeployment;
	use Deployment;


	class CliRunner extends Deployment\CliRunner
	{
		protected function loadConfigFile($file)
		{
			$configurator = new Configurator;
			$configurator->addFile($file, TRUE);

			return $configurator->getConfig();
		}
	}
